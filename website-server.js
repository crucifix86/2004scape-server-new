const express = require('express');
const path = require('path');
const { exec } = require('child_process');
const fs = require('fs');

const app = express();
const PORT = 8080;

// Serve static files
app.use('/website/css', express.static(path.join(__dirname, 'website/css')));
app.use('/website/img', express.static(path.join(__dirname, 'website/img')));
app.use('/website/downloads', express.static(path.join(__dirname, 'website/downloads')));
app.use('/website/client', express.static(path.join(__dirname, 'website/client')));

// Proxy PHP files through PHP-FPM
app.get('/website/*.php', (req, res) => {
    const phpFile = path.join(__dirname, req.path.replace('/website', '/website'));
    
    if (!fs.existsSync(phpFile)) {
        res.status(404).send('File not found');
        return;
    }
    
    exec(`php ${phpFile}`, (error, stdout, stderr) => {
        if (error) {
            console.error(`Error executing PHP: ${error}`);
            res.status(500).send('Server error');
            return;
        }
        
        // Parse headers if any
        const lines = stdout.split('\n');
        let headersDone = false;
        let body = '';
        
        for (const line of lines) {
            if (!headersDone && line === '') {
                headersDone = true;
                continue;
            }
            
            if (!headersDone && line.includes(':')) {
                const [key, value] = line.split(':');
                res.setHeader(key.trim(), value.trim());
            } else {
                body += line + '\n';
            }
        }
        
        res.send(body || stdout);
    });
});

// Default route for website
app.get('/website/', (req, res) => {
    exec(`php ${path.join(__dirname, 'website/index.php')}`, (error, stdout, stderr) => {
        if (error) {
            console.error(`Error executing PHP: ${error}`);
            res.status(500).send('Server error');
            return;
        }
        res.send(stdout);
    });
});

// Link to game client
app.get('/play', (req, res) => {
    res.redirect('http://localhost:8888/rs2.cgi?lowmem=0&plugin=0');
});

app.listen(PORT, () => {
    console.log(`Website server running on http://localhost:${PORT}/website/`);
    console.log(`Game client available at http://localhost:8888/rs2.cgi`);
});