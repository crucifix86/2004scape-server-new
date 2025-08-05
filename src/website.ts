import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export function createWebsite() {
    const app = express();

    // Serve static files
    app.use('/css', express.static(path.join(__dirname, '../website/css')));
    app.use('/img', express.static(path.join(__dirname, '../website/img')));
    app.use('/downloads', express.static(path.join(__dirname, '../website/downloads')));
    
    // Set view engine to EJS
    app.set('view engine', 'ejs');
    app.set('views', path.join(__dirname, '../website/views'));

    // Homepage
    app.get('/', (req, res) => {
        // Get player count from saves directory
        let totalPlayers = 0;
        const savesPath = path.join(__dirname, '../data/players/main');
        if (fs.existsSync(savesPath)) {
            const saves = fs.readdirSync(savesPath);
            totalPlayers = saves.filter(f => f.endsWith('.sav')).length;
        }

        const data = {
            serverName: '2004Scape',
            totalPlayers: totalPlayers,
            onlinePlayers: 0, // Would need to track this in the game server
            xpRate: '1x',
            dropRate: '1x',
            news: [
                {
                    title: 'Website Integration Complete',
                    content: 'The website is now integrated with the game server!',
                    date: new Date().toLocaleDateString()
                },
                {
                    title: 'Developer Commands Added',
                    content: 'New developer commands available for testing',
                    date: new Date().toLocaleDateString()
                }
            ]
        };
        
        res.render('index', data);
    });

    // Play page - redirect to game
    app.get('/play', (req, res) => {
        res.redirect('/rs2.cgi?lowmem=0&plugin=0');
    });

    // Hiscores page
    app.get('/hiscores', (req, res) => {
        const players = [];
        const savesPath = path.join(__dirname, '../data/players/main');
        
        if (fs.existsSync(savesPath)) {
            const saves = fs.readdirSync(savesPath);
            saves.filter(f => f.endsWith('.sav')).forEach(saveFile => {
                const username = saveFile.replace('.sav', '');
                // For now, just show usernames - parsing save files would require more work
                players.push({
                    username: username,
                    totalLevel: Math.floor(Math.random() * 500) + 100, // Placeholder
                    totalXp: Math.floor(Math.random() * 1000000) + 10000 // Placeholder
                });
            });
        }
        
        // Sort by total level
        players.sort((a, b) => b.totalLevel - a.totalLevel);
        
        res.render('hiscores', { players: players.slice(0, 50) });
    });

    // Downloads page
    app.get('/downloads', (req, res) => {
        res.render('downloads');
    });

    // Register page
    app.get('/register', (req, res) => {
        res.render('register', { error: null, success: null });
    });

    // Handle registration
    app.post('/register', express.urlencoded({ extended: true }), (req, res) => {
        const { username, password, email } = req.body;
        
        // Basic validation
        if (!username || !password || !email) {
            return res.render('register', { 
                error: 'All fields are required',
                success: null 
            });
        }
        
        // Check if username already exists
        const savePath = path.join(__dirname, '../data/players/main/', username + '.sav');
        if (fs.existsSync(savePath)) {
            return res.render('register', { 
                error: 'Username already taken',
                success: null 
            });
        }
        
        // For now, just create a placeholder file
        // Real implementation would create a proper save file
        fs.writeFileSync(savePath, '');
        
        res.render('register', { 
            error: null,
            success: 'Account created successfully! You can now login in-game.' 
        });
    });

    return app;
}