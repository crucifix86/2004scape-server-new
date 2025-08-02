# 2004Scape Server Setup Guide

## Prerequisites

1. **Node.js** - Version 20+ works, but version 22 is recommended
   ```bash
   node --version  # Check current version
   ```

2. **Java 17** - Required for the RuneScript compiler
   ```bash
   java -version  # Should show version 17 or higher
   ```

## Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/2004Scape/Server.git
   cd Server
   ```

2. **Run the quickstart script**
   ```bash
   ./quickstart.sh
   ```
   
   During setup, select: **"Game server only, using sqlite"**

3. **Wait for initialization**
   - The server will download dependencies
   - Pack the server and client cache
   - You'll see: `World ready: Visit http://localhost:8888/rs2.cgi`

## Configuration (Optional)

Create a `.env` file to customize settings:
```bash
WEB_PORT=8888
NODE_DEBUG=true
```

## Starting the Server

### Quick Start (Recommended)
```bash
npm run quickstart
```

### Development Mode (Auto-reload on changes)
```bash
npm run dev
```

### Production Mode
```bash
npm start
```

### Run in Background
```bash
npm run quickstart > server.log 2>&1 &
```

## Accessing the Game

Open your browser and go to:
**http://localhost:8888/rs2.cgi**

## Checking Server Status

Check if server is running:
```bash
ss -tuln | grep 8888
```

Check server logs:
```bash
tail -f server.log
```

## Troubleshooting

- **Port 8888 in use**: Change `WEB_PORT` in `.env` file
- **White screen**: Clear browser cache, enable JavaScript
- **Server won't start**: Check Node.js and Java versions
- **Linux default port**: Server uses port 8888 on Linux by default

## Stopping the Server

```bash
pkill -f "tsx src/app.ts"
# or
# Ctrl+C if running in foreground
```