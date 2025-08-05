#!/bin/bash
# Sync accounts from web directory to game server

WEB_SAVES="/var/www/html/2004scape/data/saves"
GAME_SAVES="/home/crucifix/Server/data/saves"

# Create game saves directory if it doesn't exist
mkdir -p "$GAME_SAVES"

# Copy all account files from web to game server
if [ -d "$WEB_SAVES" ]; then
    echo "Syncing accounts from web to game server..."
    cp -f "$WEB_SAVES"/*.json "$GAME_SAVES/" 2>/dev/null
    
    # Count accounts
    COUNT=$(ls -1 "$GAME_SAVES"/*.json 2>/dev/null | wc -l)
    echo "Synced $COUNT accounts"
fi