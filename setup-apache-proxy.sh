#!/bin/bash

# Setup Apache Reverse Proxy for 2004scape

echo "Setting up Apache reverse proxy for 2004scape..."

# Enable required modules
echo "Enabling Apache modules..."
sudo a2enmod proxy proxy_http proxy_wstunnel rewrite headers

# Create the Apache configuration
echo "Creating Apache configuration..."
sudo tee /etc/apache2/sites-available/2004scape.conf > /dev/null << 'EOF'
<VirtualHost *:80>
    # Update this with your domain name or use the server's IP
    # ServerName example.com
    # ServerAlias www.example.com
    
    # If no domain, comment out ServerName and it will work with IP
    
    # Proxy settings for Node.js app
    ProxyRequests Off
    ProxyPreserveHost On
    
    # Main website
    ProxyPass / http://localhost:8888/
    ProxyPassReverse / http://localhost:8888/
    
    # WebSocket support for game
    RewriteEngine On
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/?(.*) "ws://localhost:8888/$1" [P,L]
    
    # Headers for proper proxying
    RequestHeader set X-Forwarded-Proto "http"
    RequestHeader set X-Forwarded-Port "80"
    
    # Error and access logs
    ErrorLog ${APACHE_LOG_DIR}/2004scape-error.log
    CustomLog ${APACHE_LOG_DIR}/2004scape-access.log combined
</VirtualHost>
EOF

# Disable default site and enable 2004scape
echo "Enabling 2004scape site..."
sudo a2dissite 000-default
sudo a2ensite 2004scape

# Test configuration
echo "Testing Apache configuration..."
sudo apache2ctl configtest

# Restart Apache
echo "Restarting Apache..."
sudo systemctl restart apache2

echo "Setup complete!"
echo ""
echo "Your 2004scape server should now be accessible at:"
echo "  http://YOUR-SERVER-IP/"
echo ""
echo "To add a domain name:"
echo "  1. Edit /etc/apache2/sites-available/2004scape.conf"
echo "  2. Uncomment and update the ServerName directive"
echo "  3. Run: sudo systemctl reload apache2"
echo ""
echo "To check if it's working:"
echo "  curl http://localhost/"