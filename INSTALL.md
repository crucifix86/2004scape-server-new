# 2004scape Server Installation Guide

## Quick Install (Fresh VPS)

For a fresh Ubuntu/Debian VPS, simply run:

```bash
wget https://raw.githubusercontent.com/crucifix86/2004scape-server-new/main/install-vps.sh
chmod +x install-vps.sh
sudo ./install-vps.sh
```

The installer will:
- Install all required dependencies (Node.js, Apache, SQLite)
- Set up the database with a fresh schema
- Create your developer account
- Configure Apache as a reverse proxy
- Set up systemd service for auto-start
- Configure firewall rules

## Manual Installation

### Prerequisites

- Node.js 20 or higher
- SQLite3
- Apache2 (for reverse proxy)
- Git

### Steps

1. **Clone the repository:**
```bash
git clone https://github.com/crucifix86/2004scape-server-new.git
cd 2004scape-server
```

2. **Install dependencies:**
```bash
npm install
```

3. **Configure environment:**
```bash
cp .env.example .env
# Edit .env and set:
# BUILD_VERIFY=false
# LOGIN_SERVER=true
```

4. **Build the project:**
```bash
npm run build
```

5. **Add your developer username:**
Edit `data/developers.txt` and add your username.

6. **Start the server:**
```bash
npm run dev
```

The server will be available at `http://localhost:8888`

### Setting up Apache Reverse Proxy

To run without port numbers:

1. **Enable required modules:**
```bash
sudo a2enmod proxy proxy_http proxy_wstunnel rewrite headers
```

2. **Copy the Apache config:**
```bash
sudo cp 2004scape-apache.conf /etc/apache2/sites-available/
sudo a2ensite 2004scape
sudo a2dissite 000-default
sudo systemctl restart apache2
```

## First Time Setup

After installation:

1. Visit `/admin` to access the admin panel
2. Create your account through the registration page
3. The account will automatically have developer privileges if the username matches what's in `developers.txt`

## Server Management

- **Start:** `./server start` or `systemctl start 2004scape`
- **Stop:** `./server stop` or `systemctl stop 2004scape`
- **Restart:** `./server restart` or `systemctl restart 2004scape`
- **View logs:** `./server logs` or `journalctl -u 2004scape -f`

## Default Settings

- XP Rate: 10x
- Drop Rate: 10x (not yet implemented)
- Starting Gold: 20,999gp
- Max Players: 2000

These can be changed in the admin panel under Settings.

## Troubleshooting

1. **Port already in use:** Make sure no other services are using ports 80, 8888, 43594, or 43500
2. **Database errors:** Delete `db.sqlite` and restart the server to create a fresh database
3. **Apache issues:** Check logs with `sudo tail -f /var/log/apache2/error.log`

## Security Notes

- Always use HTTPS in production (use Certbot for free SSL certificates)
- Change default passwords immediately
- Keep the server updated regularly
- Monitor the admin login logs