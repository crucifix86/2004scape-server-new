#!/bin/bash

# 2004scape VPS Installer Script
# This script sets up a fresh VPS with everything needed to run 2004scape

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}======================================"
echo "  2004scape VPS Installation Script"
echo "======================================${NC}"
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script must be run as root${NC}" 
   exit 1
fi

# Get developer username
echo -e "${YELLOW}Enter the username you want to use as a developer (will have admin access):${NC}"
read -r DEV_USERNAME
if [[ -z "$DEV_USERNAME" ]]; then
    echo -e "${RED}Username cannot be empty${NC}"
    exit 1
fi

# Get developer password
echo -e "${YELLOW}Enter the password for the developer account:${NC}"
read -s -r DEV_PASSWORD
echo ""
if [[ -z "$DEV_PASSWORD" ]]; then
    echo -e "${RED}Password cannot be empty${NC}"
    exit 1
fi

# Get domain name (optional)
echo -e "${YELLOW}Enter your domain name (or press Enter to use IP only):${NC}"
read -r DOMAIN_NAME

# Update system
echo -e "${GREEN}Updating system packages...${NC}"
apt-get update
apt-get upgrade -y

# Install required packages
echo -e "${GREEN}Installing required packages...${NC}"
apt-get install -y curl git build-essential apache2 sqlite3 libsqlite3-dev python3 python3-pip openjdk-17-jre-headless

# Install Node.js 20
echo -e "${GREEN}Installing Node.js 20...${NC}"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

# Clone the repository
echo -e "${GREEN}Cloning 2004scape repository...${NC}"
cd /opt
if [ -d "2004scape-server" ]; then
    echo "Directory already exists, removing..."
    rm -rf 2004scape-server
fi
git clone https://github.com/crucifix86/2004scape-server-new.git 2004scape-server
cd 2004scape-server

# Install Node dependencies
echo -e "${GREEN}Installing Node.js dependencies...${NC}"
npm install

# Create .env file
echo -e "${GREEN}Creating configuration file...${NC}"
cp .env.example .env
cat >> .env << EOF

# VPS Configuration
BUILD_VERIFY=false
LOGIN_SERVER=true
NODE_ENV=production
EOF

# Initialize database
echo -e "${GREEN}Initializing database...${NC}"
# Create database initialization script
cat > init-db.mjs << EOFDB
import Database from 'better-sqlite3';
import bcrypt from 'bcrypt';
import fs from 'fs';

const db = new Database('db.sqlite');

// Create account table
db.exec(`
    CREATE TABLE IF NOT EXISTS account (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        registration_ip TEXT,
        registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        logged_in INTEGER DEFAULT 0,
        login_time DATETIME,
        logged_out INTEGER DEFAULT 0,
        logout_time DATETIME,
        muted_until DATETIME,
        banned_until DATETIME,
        staffmodlevel INTEGER DEFAULT 0,
        members INTEGER DEFAULT 0,
        email TEXT,
        password_updated DATETIME,
        oauth_provider TEXT,
        pin TEXT,
        pin_enabled INTEGER DEFAULT 0
    )
`);

// Create hiscores table
db.exec(`
    CREATE TABLE IF NOT EXISTS hiscores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        account_id INTEGER,
        username TEXT,
        rights INTEGER DEFAULT 0,
        total_xp BIGINT DEFAULT 0,
        total_level INTEGER DEFAULT 0,
        last_update DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (account_id) REFERENCES account(id)
    )
`);

// Add skill columns
const skills = [
    'attack', 'defence', 'strength', 'hitpoints', 'ranged', 'prayer', 
    'magic', 'cooking', 'woodcutting', 'fletching', 'fishing', 'firemaking',
    'crafting', 'smithing', 'mining', 'herblore', 'agility', 'thieving',
    'slayer', 'farming', 'runecraft', 'hunter', 'construction'
];

for (const skill of skills) {
    db.exec(`ALTER TABLE hiscores ADD COLUMN \${skill}_xp INTEGER DEFAULT 0`);
    db.exec(`ALTER TABLE hiscores ADD COLUMN \${skill}_level INTEGER DEFAULT 1`);
}

// Create settings table
db.exec(`
    CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
`);

// Insert default settings
const settings = [
    ['xp_rate', '10'],
    ['drop_rate', '10'],
    ['max_players', '2000'],
    ['starting_gold', '20999'],
    ['shop_prices', 'normal'],
    ['registration_enabled', 'true']
];

const stmt = db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
for (const [key, value] of settings) {
    stmt.run(key, value);
}

// Create developer account
const hashedPassword = bcrypt.hashSync('${DEV_PASSWORD}'.toLowerCase(), 10);
db.prepare(`
    INSERT INTO account (username, password, email, registration_ip, registration_date, staffmodlevel)
    VALUES (?, ?, ?, ?, datetime('now'), ?)
`).run('${DEV_USERNAME}', hashedPassword, 'admin@2004scape.com', '127.0.0.1', 2);

console.log('Database initialized successfully');
db.close();
EOFDB

# Run the initialization script
node init-db.mjs
rm init-db.mjs

# Add developer to developers.txt
echo -e "${GREEN}Adding developer to developers list...${NC}"
echo "${DEV_USERNAME}" >> data/developers.txt

# Build the project
echo -e "${GREEN}Building the project...${NC}"
npm run build

# Configure Apache
echo -e "${GREEN}Configuring Apache...${NC}"
a2enmod proxy proxy_http proxy_wstunnel rewrite headers

# Create Apache config
if [[ -n "$DOMAIN_NAME" ]]; then
    cat > /etc/apache2/sites-available/2004scape.conf << EOF
<VirtualHost *:80>
    ServerName ${DOMAIN_NAME}
    ServerAlias www.${DOMAIN_NAME}
    
    ProxyRequests Off
    ProxyPreserveHost On
    
    ProxyPass / http://localhost:8888/
    ProxyPassReverse / http://localhost:8888/
    
    RewriteEngine On
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/?(.*) "ws://localhost:8888/\$1" [P,L]
    
    RequestHeader set X-Forwarded-Proto "http"
    RequestHeader set X-Forwarded-Port "80"
    RequestHeader set X-Real-IP %{REMOTE_ADDR}s
    RequestHeader set X-Forwarded-For %{REMOTE_ADDR}s
    
    ProxyTimeout 300
    ProxyBadHeader Ignore
    
    ErrorLog \${APACHE_LOG_DIR}/2004scape-error.log
    CustomLog \${APACHE_LOG_DIR}/2004scape-access.log combined
</VirtualHost>
EOF
else
    cat > /etc/apache2/sites-available/2004scape.conf << EOF
<VirtualHost *:80>
    ProxyRequests Off
    ProxyPreserveHost On
    
    ProxyPass / http://localhost:8888/
    ProxyPassReverse / http://localhost:8888/
    
    RewriteEngine On
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/?(.*) "ws://localhost:8888/\$1" [P,L]
    
    RequestHeader set X-Forwarded-Proto "http"
    RequestHeader set X-Forwarded-Port "80"
    RequestHeader set X-Real-IP %{REMOTE_ADDR}s
    RequestHeader set X-Forwarded-For %{REMOTE_ADDR}s
    
    ProxyTimeout 300
    ProxyBadHeader Ignore
    
    ErrorLog \${APACHE_LOG_DIR}/2004scape-error.log
    CustomLog \${APACHE_LOG_DIR}/2004scape-access.log combined
</VirtualHost>
EOF
fi

a2dissite 000-default
a2ensite 2004scape
systemctl restart apache2

# Create systemd service
echo -e "${GREEN}Creating systemd service...${NC}"
cat > /etc/systemd/system/2004scape.service << EOF
[Unit]
Description=2004scape Server
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/opt/2004scape-server
ExecStart=/usr/bin/npm run dev
Restart=always
RestartSec=10
StandardOutput=append:/opt/2004scape-server/server.log
StandardError=append:/opt/2004scape-server/server.log
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target
EOF

# Start the service
systemctl daemon-reload
systemctl enable 2004scape
systemctl start 2004scape

# Configure firewall
echo -e "${GREEN}Configuring firewall...${NC}"
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 43594/tcp
ufw allow 43500/tcp
ufw --force enable

# Create credentials file
echo -e "${GREEN}Creating credentials file...${NC}"
cat > /home/2004scape-credentials.txt << EOF
=== 2004scape Server Credentials ===

Developer Account:
Username: ${DEV_USERNAME}
Password: ${DEV_PASSWORD}

Database Location: /opt/2004scape-server/db.sqlite

Admin Panel Access:
URL: http://${DOMAIN_NAME:-YOUR_SERVER_IP}/admin
Username: ${DEV_USERNAME}
Password: ${DEV_PASSWORD}

Game Client:
URL: http://${DOMAIN_NAME:-YOUR_SERVER_IP}/play

To manage the server:
- Start: systemctl start 2004scape
- Stop: systemctl stop 2004scape
- Restart: systemctl restart 2004scape
- View logs: journalctl -u 2004scape -f

Server files location: /opt/2004scape-server
EOF

echo -e "${GREEN}======================================"
echo "  Installation Complete!"
echo "======================================${NC}"
echo ""
echo -e "${YELLOW}Your credentials have been saved to: /home/2004scape-credentials.txt${NC}"
echo ""
echo -e "${GREEN}You can access your server at:${NC}"
if [[ -n "$DOMAIN_NAME" ]]; then
    echo -e "${GREEN}  Website: http://${DOMAIN_NAME}${NC}"
    echo -e "${GREEN}  Game: http://${DOMAIN_NAME}/play${NC}"
    echo -e "${GREEN}  Admin: http://${DOMAIN_NAME}/admin${NC}"
else
    echo -e "${GREEN}  Website: http://YOUR_SERVER_IP${NC}"
    echo -e "${GREEN}  Game: http://YOUR_SERVER_IP/play${NC}"
    echo -e "${GREEN}  Admin: http://YOUR_SERVER_IP/admin${NC}"
fi
echo ""
echo -e "${YELLOW}Developer account:${NC}"
echo -e "  Username: ${DEV_USERNAME}"
echo -e "  Password: [hidden]"
echo ""
echo -e "${GREEN}The server is now running!${NC}"