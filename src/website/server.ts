import express from 'express';
import session from 'express-session';
import bcrypt from 'bcrypt';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';
import Database from 'better-sqlite3';
import bodyParser from 'body-parser';
import multer from 'multer';
import { checkSpam } from './spamProtection.js';
import World from '#/engine/World.js';
import Packet from '#/io/Packet.js';
import { getLevelByExp } from '#/engine/entity/Player.js';
import { PlayerStatEnabled, PlayerStat } from '#/engine/entity/PlayerStat.js';
import InvType from '#/cache/config/InvType.js';
import ObjType from '#/cache/config/ObjType.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Initialize database
const dbPath = path.join(__dirname, '../../db.sqlite');
const db = new Database(dbPath);

// Ensure hiscores_update_interval setting exists
db.prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('hiscores_update_interval', '5')").run();

// Hiscores cache
let hiscoresCache = {
    lastUpdate: 0,
    data: {
        total: [],
        combat: [],
        wealth: []
    }
};

function updateHiscoresCache() {
    console.log('Updating hiscores cache...');
    const playerStats = [];
    const savesPath = path.join(process.cwd(), 'data/players/main');
    
    if (fs.existsSync(savesPath)) {
        const saveFiles = fs.readdirSync(savesPath).filter(f => f.endsWith('.sav'));
        
        for (const saveFile of saveFiles) {
            try {
                const username = saveFile.replace('.sav', '');
                const savePath = path.join(savesPath, saveFile);
                const saveData = fs.readFileSync(savePath);
                const packet = new Packet(saveData);
                
                // Verify save file
                if (packet.g2() !== 0x2004) continue;
                const version = packet.g2();
                if (version > 6) continue;
                
                // Skip header to stats section
                packet.pos = 4;
                packet.g2(); // x coord
                packet.g2(); // z coord
                packet.g1(); // level
                
                // Skip appearance
                for (let i = 0; i < 7; i++) packet.g1();
                for (let i = 0; i < 5; i++) packet.g1();
                packet.g1(); // gender
                packet.g2(); // runenergy
                
                if (version >= 2) {
                    packet.g4(); // playtime
                } else {
                    packet.g2(); // playtime
                }
                
                // Read stats
                const stats = [];
                let totalLevel = 0;
                let totalXp = 0;
                
                for (let i = 0; i < 21; i++) {
                    const xp = packet.g4();
                    const level = getLevelByExp(xp);
                    stats.push({ xp, level });
                    
                    if (PlayerStatEnabled[i]) {
                        totalLevel += level;
                        totalXp += xp;
                    }
                    
                    packet.g1(); // Skip current level
                }
                
                // Calculate combat level
                const attack = stats[PlayerStat.ATTACK].level;
                const defence = stats[PlayerStat.DEFENCE].level;
                const strength = stats[PlayerStat.STRENGTH].level;
                const hitpoints = stats[PlayerStat.HITPOINTS].level;
                const ranged = stats[PlayerStat.RANGED].level;
                const prayer = stats[PlayerStat.PRAYER].level;
                const magic = stats[PlayerStat.MAGIC].level;
                
                const base = 0.25 * (defence + hitpoints + Math.floor(prayer / 2));
                const melee = 0.325 * (attack + strength);
                const range = 0.325 * (Math.floor(ranged / 2) + ranged);
                const mage = 0.325 * (Math.floor(magic / 2) + magic);
                const combatLevel = Math.floor(base + Math.max(melee, range, mage));
                
                // Skip varps
                const varpCount = packet.g2();
                for (let i = 0; i < varpCount; i++) {
                    packet.g4();
                }
                
                // Read inventories for wealth
                let wealth = 0;
                const invCount = packet.g1();
                
                for (let i = 0; i < invCount; i++) {
                    const invType = packet.g2();
                    const size = version >= 5 ? packet.g2() : InvType.get(invType).size;
                    
                    for (let slot = 0; slot < size; slot++) {
                        const objId = packet.g2() - 1;
                        if (objId === -1) continue;
                        
                        let count = packet.g1();
                        if (count === 255) {
                            count = packet.g4();
                        }
                        
                        if ((invType === 93 || invType === 95) && objId === 995) {
                            wealth += count;
                        }
                    }
                }
                
                // Get staffmodlevel from database
                const account = db.prepare('SELECT staffmodlevel FROM account WHERE username = ?').get(username);
                
                playerStats.push({
                    username,
                    totalLevel,
                    totalXp,
                    combatLevel,
                    wealth,
                    stats,
                    staffmodlevel: account ? account.staffmodlevel : 0
                });
            } catch (err) {
                // Skip corrupted save files
            }
        }
    }
    
    // Sort and cache all three rankings
    hiscoresCache.data.total = [...playerStats].sort((a, b) => b.totalLevel - a.totalLevel).slice(0, 100);
    hiscoresCache.data.combat = [...playerStats].sort((a, b) => b.combatLevel - a.combatLevel).slice(0, 100);
    hiscoresCache.data.wealth = [...playerStats].sort((a, b) => b.wealth - a.wealth).slice(0, 100);
    hiscoresCache.lastUpdate = Date.now();
    
    console.log(`Hiscores cache updated with ${playerStats.length} players`);
}

// Create tables if they don't exist
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

// Add PIN columns if they don't exist (for existing databases)
try {
    db.exec(`ALTER TABLE account ADD COLUMN pin TEXT`);
} catch (e) {
    // Column already exists
}
try {
    db.exec(`ALTER TABLE account ADD COLUMN pin_enabled INTEGER DEFAULT 0`);
} catch (e) {
    // Column already exists
}

db.exec(`
    CREATE TABLE IF NOT EXISTS login (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid TEXT NOT NULL,
        account_id INTEGER NOT NULL,
        world INTEGER NOT NULL,
        ip TEXT NOT NULL,
        timestamp DATETIME NOT NULL
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS session (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uid TEXT NOT NULL,
        account_id INTEGER NOT NULL,
        timestamp DATETIME NOT NULL
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS news (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        author_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_active INTEGER DEFAULT 1
    )
`);

// Check if mod_action table exists and has correct structure
try {
    const tableInfo = db.prepare("PRAGMA table_info(mod_action)").all();
    const hasActionColumn = tableInfo.some(col => col.name === 'action');
    
    if (!hasActionColumn && tableInfo.length > 0) {
        // Table exists but is missing action column, recreate it
        db.exec('DROP TABLE IF EXISTS mod_action');
    }
} catch (err) {
    // Table doesn't exist, will be created below
}

db.exec(`
    CREATE TABLE IF NOT EXISTS mod_action (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        account_id INTEGER NOT NULL,
        target_id INTEGER,
        action TEXT NOT NULL,
        reason TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS chat_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        account_id INTEGER NOT NULL,
        username TEXT NOT NULL,
        message TEXT NOT NULL,
        chat_type TEXT NOT NULL,
        target_username TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (account_id) REFERENCES account(id)
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS admin_login_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        account_id INTEGER NOT NULL,
        username TEXT NOT NULL,
        ip_address TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (account_id) REFERENCES account(id)
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS moderator_permissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        permission_key TEXT NOT NULL UNIQUE,
        permission_name TEXT NOT NULL,
        permission_description TEXT,
        enabled INTEGER DEFAULT 1
    )
`);

db.exec(`
    CREATE TABLE IF NOT EXISTS moderator_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        moderator_id INTEGER NOT NULL,
        moderator_name TEXT NOT NULL,
        action TEXT NOT NULL,
        target_name TEXT,
        details TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (moderator_id) REFERENCES account(id)
    )
`);

// Insert default moderator permissions
const defaultPermissions = [
    { key: 'view_players', name: 'View Players', description: 'Can view player list and details' },
    { key: 'ban_players', name: 'Ban Players', description: 'Can ban and unban players' },
    { key: 'mute_players', name: 'Mute Players', description: 'Can mute and unmute players' },
    { key: 'view_chat_logs', name: 'View Chat Logs', description: 'Can view player chat logs' },
    { key: 'view_mod_logs', name: 'View Mod Logs', description: 'Can view moderator action logs' },
    { key: 'view_reports', name: 'View Reports', description: 'Can view player reports' },
    { key: 'manage_news', name: 'Manage News', description: 'Can create and edit news posts' },
    { key: 'view_settings', name: 'View Settings', description: 'Can view server settings' },
    { key: 'manage_content', name: 'Manage Content', description: 'Can edit site content and feature cards' }
];

for (const perm of defaultPermissions) {
    db.prepare(`
        INSERT OR IGNORE INTO moderator_permissions (permission_key, permission_name, permission_description, enabled)
        VALUES (?, ?, ?, 1)
    `).run(perm.key, perm.name, perm.description);
}

// Insert default settings if they don't exist
const settingsStmt = db.prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)');
settingsStmt.run('xp_rate', '1');
settingsStmt.run('drop_rate', '1');
settingsStmt.run('server_name', '2004Scape');
settingsStmt.run('akismet_enabled', 'false');
settingsStmt.run('akismet_api_key', '');
settingsStmt.run('honeypot_enabled', 'false');
settingsStmt.run('honeypot_api_key', '');

export function createWebsiteServer() {
    const app = express();
    
    // Configure multer for file uploads
    const storage = multer.diskStorage({
        destination: function (req, file, cb) {
            cb(null, path.join(__dirname, '../../website/uploads'));
        },
        filename: function (req, file, cb) {
            const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
            cb(null, 'logo-' + uniqueSuffix + path.extname(file.originalname));
        }
    });
    
    const upload = multer({ 
        storage: storage,
        limits: { fileSize: 2 * 1024 * 1024 }, // 2MB limit
        fileFilter: function (req, file, cb) {
            const allowedTypes = /jpeg|jpg|png|gif|webp/;
            const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
            const mimetype = allowedTypes.test(file.mimetype);
            
            if (mimetype && extname) {
                return cb(null, true);
            } else {
                cb(new Error('Only image files are allowed'));
            }
        }
    });

    // Middleware
    app.use(bodyParser.urlencoded({ extended: true }));
    app.use(bodyParser.json());
    app.use(session({
        secret: 'runescape2004secret',
        resave: false,
        saveUninitialized: false,
        cookie: { maxAge: 24 * 60 * 60 * 1000 } // 24 hours
    }));

    // Serve static files
    app.use('/css', express.static(path.join(__dirname, '../../website/css')));
    app.use('/img', express.static(path.join(__dirname, '../../website/img')));
    app.use('/uploads', express.static(path.join(__dirname, '../../website/uploads')));
    app.use(express.static(path.join(__dirname, '../../website')));
    
    // Set view engine to EJS
    app.set('view engine', 'ejs');
    app.set('views', path.join(__dirname, '../../website/views'));

    // Helper function to get server stats
    function getServerStats() {
        // Count total players from both database and save files
        let totalPlayers = db.prepare('SELECT COUNT(*) as count FROM account').get().count;
        
        // Also count save files
        const saveDir = path.join(__dirname, '../../data/players/main/');
        if (fs.existsSync(saveDir)) {
            const saveFiles = fs.readdirSync(saveDir).filter(f => f.endsWith('.sav'));
            // Use whichever is higher (some accounts may only exist as saves)
            totalPlayers = Math.max(totalPlayers, saveFiles.length);
        }
        
        // Get actual online players from the game world
        let onlinePlayers = 0;
        try {
            // Count non-null players in the world
            for (const player of World.players) {
                if (player && player.username) {
                    onlinePlayers++;
                }
            }
        } catch (err) {
            // Fallback to database method if World isn't available
            onlinePlayers = db.prepare(`
                SELECT COUNT(DISTINCT account_id) as count 
                FROM login 
                WHERE datetime(timestamp) > datetime('now', '-5 minutes')
            `).get().count;
        }
        
        const settings = {};
        const settingsRows = db.prepare('SELECT key, value FROM settings').all();
        settingsRows.forEach(row => {
            settings[row.key] = row.value;
        });
        
        // Server is online if World is running
        const serverOnline = World && World.gameMap ? true : false;
        
        return {
            totalPlayers,
            onlinePlayers,
            xpRate: settings.xp_rate || '1',
            dropRate: settings.drop_rate || '1',
            serverName: settings.server_name || '2004Scape',
            serverOnline
        };
    }

    // Homepage
    app.get('/', (req, res) => {
        const stats = getServerStats();
        
        // Get recent news
        const news = db.prepare(`
            SELECT * FROM news 
            WHERE is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 5
        `).all();
        
        // Get site content
        const content = {};
        try {
            const contentRows = db.prepare('SELECT key, value FROM site_content').all();
            contentRows.forEach(row => {
                if (row.key === 'features') {
                    content.features = JSON.parse(row.value || '[]');
                } else {
                    content[row.key] = row.value;
                }
            });
        } catch (err) {
            // Use defaults
            content.features = [
                { icon: '⚔️', title: 'Classic Combat', description: 'Original 3-hit combat system with authentic timing and mechanics' },
                { icon: '🗺️', title: 'Original World', description: 'Explore the 2004 world map with all original locations' },
                { icon: '🎯', title: 'No Pay-to-Win', description: 'Pure gameplay experience with no microtransactions' },
                { icon: '🛠️', title: 'Developer Tools', description: 'Special commands for testing and development' }
            ];
        }
        
        // Get theme
        const theme = {};
        try {
            const themeRows = db.prepare('SELECT key, value FROM theme_settings').all();
            themeRows.forEach(row => {
                theme[row.key] = row.value;
            });
        } catch (err) {
            // Use defaults
            theme.primary_color = '#ffd700';
            theme.secondary_color = '#2a5298';
            theme.background_color = '#1a1a1a';
            theme.text_color = '#ffffff';
            theme.accent_color = '#8b4513';
        }
        
        res.render('index', {
            ...stats,
            news,
            user: req.session.user || null,
            serverOnline: stats.serverOnline,
            serverName: stats.serverName,
            content,
            theme
        });
    });

    // Play page
    app.get('/play', (req, res) => {
        if (!req.session.user) {
            return res.redirect('/login?redirect=/play');
        }
        res.redirect('/rs2.cgi');
    });
    
    // Game client with session
    app.get('/rs2.cgi', (req, res) => {
        // Pass session info to web.ts handler
        req.url = req.url + (req.url.includes('?') ? '&' : '?') + 'session_user=' + encodeURIComponent(req.session.user ? req.session.user.username : '');
        // Let web.ts handle the actual response
        return;
    });

    // Login page
    app.get('/login', (req, res) => {
        res.render('login', { 
            error: null,
            redirect: req.query.redirect || '/'
        });
    });

    // Check if user has PIN enabled
    app.post('/check-pin', (req, res) => {
        const { username } = req.body;
        
        if (!username) {
            return res.json({ hasPin: false });
        }
        
        const account = db.prepare('SELECT pin_enabled FROM account WHERE username = ?').get(username);
        
        if (!account) {
            return res.json({ hasPin: false });
        }
        
        return res.json({ hasPin: account.pin_enabled === 1 });
    });

    // Login handler - supports both JSON and form data
    app.post('/login', async (req, res) => {
        const isJson = req.headers['content-type']?.includes('application/json');
        const { username, password, pin, redirect } = req.body;
        
        // Authenticate against database like the game does
        const account = db.prepare('SELECT * FROM account WHERE username = ?').get(username);
        
        if (!account || !(await bcrypt.compare(password.toLowerCase(), account.password))) {
            // Invalid username or password
            if (isJson) {
                return res.json({ success: false, error: 'Invalid username or password' });
            } else {
                return res.redirect(redirect || '/');
            }
        }
        
        // Check PIN if enabled
        if (account.pin_enabled && account.pin) {
            if (!pin) {
                // PIN required but not provided
                if (isJson) {
                    return res.json({ success: false, error: 'PIN required', requirePin: true });
                } else {
                    return res.redirect(redirect || '/');
                }
            }
            
            if (!(await bcrypt.compare(pin, account.pin))) {
                // Invalid PIN
                if (isJson) {
                    return res.json({ success: false, error: 'Invalid PIN' });
                } else {
                    return res.redirect(redirect || '/');
                }
            }
        }
        
        // Check if user is in developers.txt for admin access
        let staffmodlevel = account.staffmodlevel;
        const devFile = path.join(__dirname, '../../data/developers.txt');
        if (fs.existsSync(devFile)) {
            const devs = fs.readFileSync(devFile, 'utf8');
            const devUsernames = devs.split('\n')
                .filter(line => line.trim() && !line.trim().startsWith('#'))
                .map(line => line.trim().toLowerCase());
            if (devUsernames.includes(username.toLowerCase())) {
                staffmodlevel = Math.max(staffmodlevel, 3); // At least admin level
            }
        }
        
        // Set session with database account info
        req.session.user = {
            id: account.id,
            username: account.username,
            staffmodlevel: staffmodlevel
        };
        
        // Log admin panel login if user has staff access
        if (staffmodlevel >= 2) {
            const ipAddress = req.ip || req.connection.remoteAddress || '';
            db.prepare(`
                INSERT INTO admin_login_log (account_id, username, ip_address)
                VALUES (?, ?, ?)
            `).run(account.id, account.username, ipAddress);
        }
        
        if (isJson) {
            return res.json({ success: true });
        } else {
            return res.redirect(redirect || '/play');
        }
    });

    // Logout
    app.get('/logout', (req, res) => {
        req.session.destroy();
        res.redirect('/');
    });
    
    // Profile page
    app.get('/profile', (req, res) => {
        if (!req.session.user) {
            return res.redirect('/login');
        }
        
        // Get fresh user data including PIN status
        const user = db.prepare('SELECT * FROM account WHERE id = ?').get(req.session.user.id);
        
        const settings = {};
        const settingsRows = db.prepare('SELECT key, value FROM settings').all();
        for (const row of settingsRows) {
            settings[row.key] = row.value;
        }
        
        res.render('profile', {
            user: user,
            serverName: settings.server_name || '2004Scape',
            content: {
                site_title: settings.server_name || '2004Scape',
                site_tagline: 'Experience RuneScape as it was in May 2004'
            },
            success: req.query.success || null,
            error: req.query.error || null
        });
    });
    
    // Update email
    app.post('/profile/email', (req, res) => {
        if (!req.session.user) {
            return res.redirect('/login');
        }
        
        const { email } = req.body;
        
        if (!email || !email.includes('@')) {
            return res.redirect('/profile?error=' + encodeURIComponent('Please enter a valid email address'));
        }
        
        try {
            db.prepare('UPDATE account SET email = ? WHERE id = ?').run(email, req.session.user.id);
            res.redirect('/profile?success=' + encodeURIComponent('Email updated successfully'));
        } catch (err) {
            res.redirect('/profile?error=' + encodeURIComponent('Failed to update email'));
        }
    });
    
    // Enable PIN
    app.post('/profile/pin/enable', (req, res) => {
        if (!req.session.user) {
            return res.redirect('/login');
        }
        
        const { new_pin, confirm_pin } = req.body;
        
        if (!new_pin || !/^\d{4}$/.test(new_pin)) {
            return res.redirect('/profile?error=' + encodeURIComponent('PIN must be exactly 4 digits'));
        }
        
        if (new_pin !== confirm_pin) {
            return res.redirect('/profile?error=' + encodeURIComponent('PINs do not match'));
        }
        
        try {
            const hashedPin = bcrypt.hashSync(new_pin, 10);
            db.prepare('UPDATE account SET pin = ?, pin_enabled = 1 WHERE id = ?').run(hashedPin, req.session.user.id);
            res.redirect('/profile?success=' + encodeURIComponent('PIN security enabled successfully'));
        } catch (err) {
            res.redirect('/profile?error=' + encodeURIComponent('Failed to enable PIN'));
        }
    });
    
    // Change PIN
    app.post('/profile/pin/change', (req, res) => {
        if (!req.session.user) {
            return res.redirect('/login');
        }
        
        const { current_pin, new_pin, confirm_pin } = req.body;
        const user = db.prepare('SELECT pin FROM account WHERE id = ?').get(req.session.user.id);
        
        if (!user || !bcrypt.compareSync(current_pin, user.pin)) {
            return res.redirect('/profile?error=' + encodeURIComponent('Current PIN is incorrect'));
        }
        
        if (!new_pin || !/^\d{4}$/.test(new_pin)) {
            return res.redirect('/profile?error=' + encodeURIComponent('PIN must be exactly 4 digits'));
        }
        
        if (new_pin !== confirm_pin) {
            return res.redirect('/profile?error=' + encodeURIComponent('PINs do not match'));
        }
        
        try {
            const hashedPin = bcrypt.hashSync(new_pin, 10);
            db.prepare('UPDATE account SET pin = ? WHERE id = ?').run(hashedPin, req.session.user.id);
            res.redirect('/profile?success=' + encodeURIComponent('PIN changed successfully'));
        } catch (err) {
            res.redirect('/profile?error=' + encodeURIComponent('Failed to change PIN'));
        }
    });
    
    // Disable PIN
    app.post('/profile/pin/disable', (req, res) => {
        if (!req.session.user) {
            return res.redirect('/login');
        }
        
        const { current_pin } = req.body;
        const user = db.prepare('SELECT pin FROM account WHERE id = ?').get(req.session.user.id);
        
        if (!user || !bcrypt.compareSync(current_pin, user.pin)) {
            return res.redirect('/profile?error=' + encodeURIComponent('PIN is incorrect'));
        }
        
        try {
            db.prepare('UPDATE account SET pin = NULL, pin_enabled = 0 WHERE id = ?').run(req.session.user.id);
            res.redirect('/profile?success=' + encodeURIComponent('PIN security disabled'));
        } catch (err) {
            res.redirect('/profile?error=' + encodeURIComponent('Failed to disable PIN'));
        }
    });
    
    // Change password
    app.post('/profile/password', (req, res) => {
        if (!req.session.user) {
            return res.redirect('/login');
        }
        
        const { current_password, new_password, confirm_password } = req.body;
        const user = db.prepare('SELECT password FROM account WHERE id = ?').get(req.session.user.id);
        
        if (!user || !bcrypt.compareSync(current_password.toLowerCase(), user.password)) {
            return res.redirect('/profile?error=' + encodeURIComponent('Current password is incorrect'));
        }
        
        if (new_password.length < 5 || new_password.length > 20) {
            return res.redirect('/profile?error=' + encodeURIComponent('Password must be between 5 and 20 characters'));
        }
        
        if (new_password !== confirm_password) {
            return res.redirect('/profile?error=' + encodeURIComponent('Passwords do not match'));
        }
        
        try {
            const hashedPassword = bcrypt.hashSync(new_password.toLowerCase(), 10);
            db.prepare('UPDATE account SET password = ?, password_updated = datetime(\'now\') WHERE id = ?').run(hashedPassword, req.session.user.id);
            res.redirect('/profile?success=' + encodeURIComponent('Password changed successfully'));
        } catch (err) {
            res.redirect('/profile?error=' + encodeURIComponent('Failed to change password'));
        }
    });

    // Register page
    app.get('/register', (req, res) => {
        // Check if registration is allowed
        const settings = db.prepare('SELECT value FROM settings WHERE key = ?').get('allow_registration');
        const registrationEnabled = !settings || settings.value !== 'false';
        
        if (!registrationEnabled) {
            return res.render('register', { 
                error: 'Registration is currently disabled', 
                success: null,
                disabled: true
            });
        }
        
        res.render('register', { 
            error: null, 
            success: null,
            disabled: false
        });
    });

    // Register handler
    app.post('/register', async (req, res) => {
        // Check if registration is allowed
        const regSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('allow_registration');
        const registrationEnabled = !regSetting || regSetting.value !== 'false';
        
        if (!registrationEnabled) {
            return res.render('register', { 
                error: 'Registration is currently disabled',
                success: null,
                disabled: true
            });
        }
        
        const { username, password, password_confirm, email, captcha, captcha_answer } = req.body;
        
        // Validation
        if (!username || !password || !email || !captcha || !captcha_answer) {
            return res.render('register', { 
                error: 'All fields are required',
                success: null 
            });
        }
        
        // Validate captcha
        try {
            const decodedAnswer = Buffer.from(captcha_answer, 'base64').toString('utf-8');
            if (captcha !== decodedAnswer) {
                return res.render('register', { 
                    error: 'Incorrect answer to the security question',
                    success: null 
                });
            }
        } catch (err) {
            return res.render('register', { 
                error: 'Invalid security question answer',
                success: null 
            });
        }
        
        if (password !== password_confirm) {
            return res.render('register', { 
                error: 'Passwords do not match',
                success: null 
            });
        }
        
        if (username.length < 3 || username.length > 12) {
            return res.render('register', { 
                error: 'Username must be between 3 and 12 characters',
                success: null 
            });
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            return res.render('register', { 
                error: 'Username can only contain letters, numbers, and underscores',
                success: null 
            });
        }
        
        if (password.length < 5 || password.length > 20) {
            return res.render('register', { 
                error: 'Password must be between 5 and 20 characters',
                success: null 
            });
        }
        
        // Check if username exists
        const existing = db.prepare('SELECT id FROM account WHERE LOWER(username) = LOWER(?)').get(username);
        if (existing) {
            return res.render('register', { 
                error: 'Username already taken',
                success: null 
            });
        }
        
        // Get settings for spam protection
        const settings = {};
        const settingsRows = db.prepare('SELECT key, value FROM settings').all();
        for (const row of settingsRows) {
            settings[row.key] = row.value;
        }
        
        // Check for spam
        const ip = req.ip || req.connection.remoteAddress || '127.0.0.1';
        const userAgent = req.headers['user-agent'] || '';
        const siteUrl = `${req.protocol}://${req.get('host')}`;
        
        const spamResult = await checkSpam(settings, ip, userAgent, username, email, siteUrl);
        if (spamResult.isSpam) {
            return res.render('register', { 
                error: 'Registration blocked: ' + (spamResult.reason || 'Spam protection triggered'),
                success: null 
            });
        }
        
        // Create account - game uses lowercase password
        const hashedPassword = bcrypt.hashSync(password.toLowerCase(), 10);
        
        try {
            const result = db.prepare(`
                INSERT INTO account (username, password, email, registration_ip, registration_date)
                VALUES (?, ?, ?, ?, datetime('now'))
            `).run(username, hashedPassword, email, ip);
            
            // Auto-login the user
            const newUser = db.prepare('SELECT id, username, staffmodlevel FROM account WHERE id = ?').get(result.lastInsertRowid);
            req.session.user = newUser;
            
            // Redirect to homepage
            res.redirect('/');
        } catch (err) {
            res.render('register', { 
                error: 'Failed to create account. Please try again.',
                success: null 
            });
        }
    });

    // Hiscores
    // Initialize/update cache on startup
    updateHiscoresCache();
    
    // Set up automatic cache updates
    setInterval(() => {
        const updateInterval = parseInt(db.prepare('SELECT value FROM settings WHERE key = ?').get('hiscores_update_interval')?.value || '5');
        updateHiscoresCache();
    }, 60000); // Check every minute if update is needed
    
    app.get('/hiscores', (req, res) => {
        const sortBy = req.query.sort || 'total'; // total, combat, wealth
        
        // Check if cache needs updating
        const updateInterval = parseInt(db.prepare('SELECT value FROM settings WHERE key = ?').get('hiscores_update_interval')?.value || '5');
        const updateIntervalMs = updateInterval * 60 * 1000;
        
        if (Date.now() - hiscoresCache.lastUpdate > updateIntervalMs) {
            updateHiscoresCache();
        }
        
        // Get cached data based on sort type
        const players = hiscoresCache.data[sortBy] || hiscoresCache.data.total;
        
        // Get site content
        const content = {};
        try {
            const contentRows = db.prepare('SELECT key, value FROM site_content').all();
            contentRows.forEach(row => {
                if (row.key === 'features') {
                    content.features = JSON.parse(row.value || '[]');
                } else {
                    content[row.key] = row.value;
                }
            });
        } catch (err) {
            // Table might not exist
        }
        
        // Get theme settings
        const theme = {};
        try {
            const themeRows = db.prepare('SELECT key, value FROM theme_settings').all();
            themeRows.forEach(row => {
                theme[row.key] = row.value;
            });
        } catch (err) {
            // Use defaults
        }
        
        res.render('hiscores', { 
            players: players,
            user: req.session.user || null,
            content,
            theme,
            sortBy
        });
    });

    // Helper function to check moderator permissions
    function hasPermission(user, permission) {
        // Developers (level 4) have all permissions
        if (user.staffmodlevel >= 4) return true;
        
        // Regular players (level 0) have no permissions
        if (user.staffmodlevel < 2) return false;
        
        // Moderators (level 2) check permission table
        if (user.staffmodlevel === 2) {
            const perm = db.prepare('SELECT enabled FROM moderator_permissions WHERE permission_key = ?').get(permission);
            return perm && perm.enabled === 1;
        }
        
        // Admin level (3) - treat as moderator for now
        if (user.staffmodlevel === 3) {
            const perm = db.prepare('SELECT enabled FROM moderator_permissions WHERE permission_key = ?').get(permission);
            return perm && perm.enabled === 1;
        }
        
        return false;
    }
    
    // Admin panel - Check authentication
    function requireAdmin(req, res, next) {
        if (!req.session.user || req.session.user.staffmodlevel < 2) {
            return res.redirect('/login?redirect=/admin');
        }
        req.hasPermission = (permission) => hasPermission(req.session.user, permission);
        // Make hasPermission available in views
        res.locals.hasPermission = req.hasPermission;
        next();
    }

    // Admin dashboard
    app.get('/admin', requireAdmin, (req, res) => {
        const stats = {
            total_accounts: db.prepare('SELECT COUNT(*) as count FROM account').get().count,
            online_players: db.prepare(`
                SELECT COUNT(DISTINCT account_id) as count 
                FROM login 
                WHERE datetime(timestamp) > datetime('now', '-5 minutes')
            `).get().count,
            banned_accounts: db.prepare(`
                SELECT COUNT(*) as count 
                FROM account 
                WHERE datetime(banned_until) > datetime('now')
            `).get().count,
            muted_accounts: db.prepare(`
                SELECT COUNT(*) as count 
                FROM account 
                WHERE datetime(muted_until) > datetime('now')
            `).get().count,
            recent_registrations: db.prepare(`
                SELECT COUNT(*) as count 
                FROM account 
                WHERE datetime(registration_date) > datetime('now', '-1 day')
            `).get().count
        };
        
        const recent_logins = db.prepare(`
            SELECT username, timestamp as login_time, ip_address
            FROM admin_login_log 
            ORDER BY timestamp DESC 
            LIMIT 10
        `).all();
        
        const recent_actions = db.prepare(`
            SELECT ma.*, a.username as mod_username, t.username as target_username
            FROM mod_action ma
            LEFT JOIN account a ON ma.account_id = a.id
            LEFT JOIN account t ON ma.target_id = t.id
            ORDER BY ma.timestamp DESC
            LIMIT 10
        `).all();
        
        res.render('admin/dashboard', {
            user: req.session.user,
            stats,
            recent_logins,
            recent_actions
        });
    });

    // Admin - Players management
    app.get('/admin/players', requireAdmin, (req, res) => {
        // Check permission
        if (!req.hasPermission('view_players')) {
            return res.render('admin/error', {
                user: req.session.user,
                error: 'You do not have permission to view players.'
            });
        }
        const search = req.query.search || '';
        let players;
        
        // Get players from database
        if (search) {
            players = db.prepare(`
                SELECT * FROM account 
                WHERE username LIKE ? 
                ORDER BY id DESC 
                LIMIT 100
            `).all(`%${search}%`);
        } else {
            players = db.prepare(`
                SELECT * FROM account 
                ORDER BY id DESC 
                LIMIT 100
            `).all();
        }
        
        // Check if each player is online
        players = players.map(player => {
            let isOnline = false;
            try {
                for (const p of World.players) {
                    if (p && p.username && p.username.toLowerCase() === player.username.toLowerCase()) {
                        isOnline = true;
                        break;
                    }
                }
            } catch (err) {
                // World not available
            }
            return {
                ...player,
                logged_in: isOnline ? 1 : 0
            };
        });
        
        res.render('admin/players', {
            user: req.session.user,
            players,
            search
        });
    });

    // Admin - Ban player
    app.post('/admin/ban', requireAdmin, (req, res) => {
        // Check permission
        if (!req.hasPermission('ban_players')) {
            return res.redirect('/admin/players');
        }
        const { player_id, duration, reason, unban } = req.body;
        
        // Handle unban
        if (unban === 'true') {
            db.prepare('UPDATE account SET banned_until = NULL WHERE id = ?').run(player_id);
            
            // Log the action
            db.prepare(`
                INSERT INTO mod_action (account_id, target_id, action, reason)
                VALUES (?, ?, 'unban', 'Manual unban')
            `).run(req.session.user.id, player_id);
            
            res.redirect('/admin/players');
            return;
        }
        
        // Handle ban
        let banUntil;
        if (duration === 'permanent') {
            banUntil = '9999-12-31 23:59:59';
        } else {
            const hours = parseInt(duration);
            banUntil = new Date(Date.now() + hours * 60 * 60 * 1000).toISOString();
        }
        
        db.prepare('UPDATE account SET banned_until = ? WHERE id = ?').run(banUntil, player_id);
        
        // Log the action
        db.prepare(`
            INSERT INTO mod_action (account_id, target_id, action, reason)
            VALUES (?, ?, 'ban', ?)
        `).run(req.session.user.id, player_id, reason);
        
        res.redirect('/admin/players');
    });

    // Admin - Mute player
    app.post('/admin/mute', requireAdmin, (req, res) => {
        // Check permission
        if (!req.hasPermission('mute_players')) {
            return res.redirect('/admin/players');
        }
        const { player_id, duration, reason, unmute } = req.body;
        
        // Handle unmute
        if (unmute === 'true') {
            db.prepare('UPDATE account SET muted_until = NULL WHERE id = ?').run(player_id);
            
            // Log the action
            db.prepare(`
                INSERT INTO mod_action (account_id, target_id, action, reason)
                VALUES (?, ?, 'unmute', 'Manual unmute')
            `).run(req.session.user.id, player_id);
            
            res.redirect('/admin/players');
            return;
        }
        
        // Handle mute
        let muteUntil;
        if (duration === 'permanent') {
            muteUntil = '9999-12-31 23:59:59';
        } else {
            const hours = parseInt(duration);
            muteUntil = new Date(Date.now() + hours * 60 * 60 * 1000).toISOString();
        }
        
        db.prepare('UPDATE account SET muted_until = ? WHERE id = ?').run(muteUntil, player_id);
        
        // Log the action
        db.prepare(`
            INSERT INTO mod_action (account_id, target_id, action, reason)
            VALUES (?, ?, 'mute', ?)
        `).run(req.session.user.id, player_id, reason);
        
        res.redirect('/admin/players');
    });
    
    // Admin - Reset PIN (Developers only)
    app.post('/admin/reset_pin', requireAdmin, (req, res) => {
        const { player_id } = req.body;
        
        // Only developers can reset PINs
        if (req.session.user.staffmodlevel < 4) {
            return res.redirect('/admin/players');
        }
        
        // Get player info for logging
        const target = db.prepare('SELECT username FROM account WHERE id = ?').get(player_id);
        if (!target) {
            return res.redirect('/admin/players');
        }
        
        // Reset PIN - disable PIN security
        db.prepare('UPDATE account SET pin = NULL, pin_enabled = 0 WHERE id = ?').run(player_id);
        
        // Log the action
        db.prepare(`
            INSERT INTO mod_action (account_id, target_id, action, reason)
            VALUES (?, ?, 'reset_pin', 'PIN security disabled for user')
        `).run(req.session.user.id, player_id);
        
        res.redirect('/admin/players');
    });

    // Admin - Bans & Mutes page
    app.get('/admin/bans', requireAdmin, (req, res) => {
        const banned = db.prepare(`
            SELECT * FROM account 
            WHERE datetime(banned_until) > datetime('now')
            ORDER BY banned_until DESC
        `).all();
        
        const muted = db.prepare(`
            SELECT * FROM account 
            WHERE datetime(muted_until) > datetime('now')
            ORDER BY muted_until DESC
        `).all();
        
        res.render('admin/bans', {
            user: req.session.user,
            banned,
            muted
        });
    });

    // Admin - Reports page
    app.get('/admin/reports', requireAdmin, (req, res) => {
        // Check permission
        if (!req.hasPermission('view_reports')) {
            return res.render('admin/error', {
                user: req.session.user,
                error: 'You do not have permission to view reports.'
            });
        }
        // For now, empty reports since report system needs to be implemented in game
        const reports = [];
        
        res.render('admin/reports', {
            user: req.session.user,
            reports
        });
    });

    // Admin - Chat logs
    app.get('/admin/chat', requireAdmin, (req, res) => {
        // Check permission
        if (!req.hasPermission('view_chat_logs')) {
            return res.render('admin/error', {
                user: req.session.user,
                error: 'You do not have permission to view chat logs.'
            });
        }
        const search = req.query.search || '';
        const filter = req.query.filter || 'all';
        const page = parseInt(req.query.page as string) || 1;
        const limit = 100;
        const offset = (page - 1) * limit;
        
        let query = `
            SELECT cl.*, a.staffmodlevel 
            FROM chat_log cl
            JOIN account a ON cl.account_id = a.id
            WHERE 1=1
        `;
        const params: any[] = [];
        
        if (search) {
            query += ` AND (cl.username LIKE ? OR cl.message LIKE ? OR cl.target_username LIKE ?)`;
            params.push(`%${search}%`, `%${search}%`, `%${search}%`);
        }
        
        if (filter === 'public') {
            query += ` AND cl.chat_type = 'public'`;
        } else if (filter === 'private') {
            query += ` AND cl.chat_type = 'private'`;
        } else if (filter === 'broadcast') {
            query += ` AND cl.chat_type = 'broadcast'`;
        }
        
        // Get total count
        const countQuery = query.replace('cl.*, a.staffmodlevel', 'COUNT(*) as count');
        const totalCount = db.prepare(countQuery).get(...params).count;
        const totalPages = Math.ceil(totalCount / limit);
        
        // Get chats
        query += ` ORDER BY cl.timestamp DESC LIMIT ? OFFSET ?`;
        params.push(limit, offset);
        
        const chats = db.prepare(query).all(...params);
        
        res.render('admin/chat', {
            user: req.session.user,
            chats,
            search,
            filter,
            page,
            totalPages
        });
    });

    // Admin - Broadcast system message
    app.post('/admin/broadcast', requireAdmin, (req, res) => {
        const { message } = req.body;
        
        if (!message || message.trim().length === 0) {
            return res.redirect('/admin/chat');
        }
        
        // Broadcast message to all online players
        try {
            for (const player of World.players) {
                if (player && player.client) {
                    player.messageGame(`[System] ${message}`);
                }
            }
            
            // Log the broadcast
            db.prepare(`
                INSERT INTO mod_action (account_id, action, reason, timestamp)
                VALUES (?, 'broadcast', ?, datetime('now'))
            `).run(req.session.user.id, message);
            
            // Also log to chat_log for viewing in chat logs
            db.prepare(`
                INSERT INTO chat_log (account_id, username, message, chat_type, target_username)
                VALUES (?, ?, ?, 'broadcast', NULL)
            `).run(req.session.user.id, req.session.user.username, message);
        } catch (err) {
            console.error('Error broadcasting message:', err);
        }
        
        res.redirect('/admin/chat');
    });
    
    // Admin - Mod logs
    app.get('/admin/mod_logs', requireAdmin, (req, res) => {
        // Check permission
        if (!req.hasPermission('view_mod_logs')) {
            return res.render('admin/error', {
                user: req.session.user,
                error: 'You do not have permission to view moderator logs.'
            });
        }
        const logs = db.prepare(`
            SELECT ma.*, a.username as mod_username, t.username as target_username
            FROM mod_action ma
            LEFT JOIN account a ON ma.account_id = a.id
            LEFT JOIN account t ON ma.target_id = t.id
            ORDER BY ma.timestamp DESC
            LIMIT 100
        `).all();
        
        res.render('admin/mod_logs', {
            user: req.session.user,
            logs
        });
    });

    // Admin updates page
    app.get('/admin/updates', requireAdmin, (req, res) => {
        if (req.session.user.staffmodlevel < 4) {
            return res.redirect('/admin');
        }
        
        res.render('admin/updates', { 
            user: req.session.user,
            hasPermission: req.hasPermission
        });
    });
    
    // Get update settings
    app.get('/admin/update-settings', requireAdmin, (req, res) => {
        if (req.session.user.staffmodlevel < 4) {
            return res.status(403).json({ error: 'Insufficient permissions' });
        }
        
        const settings = {
            auto_backup: db.prepare('SELECT value FROM settings WHERE key = ?').get('update_auto_backup')?.value !== 'false',
            exclude_css: db.prepare('SELECT value FROM settings WHERE key = ?').get('update_exclude_css')?.value === 'true',
            exclude_index: db.prepare('SELECT value FROM settings WHERE key = ?').get('update_exclude_index')?.value === 'true',
            exclude_website_views: db.prepare('SELECT value FROM settings WHERE key = ?').get('update_exclude_views')?.value === 'true'
        };
        
        res.json(settings);
    });
    
    // Save update settings
    app.post('/admin/update-settings', requireAdmin, (req, res) => {
        if (req.session.user.staffmodlevel < 4) {
            return res.status(403).json({ error: 'Insufficient permissions' });
        }
        
        const settings = req.body;
        
        db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)').run('update_auto_backup', settings.auto_backup ? 'true' : 'false');
        db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)').run('update_exclude_css', settings.exclude_css ? 'true' : 'false');
        db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)').run('update_exclude_index', settings.exclude_index ? 'true' : 'false');
        db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)').run('update_exclude_views', settings.exclude_website_views ? 'true' : 'false');
        
        res.json({ success: true });
    });
    
    // Check for updates
    app.get('/admin/check-updates', requireAdmin, async (req, res) => {
        if (req.session.user.staffmodlevel < 4) {
            return res.status(403).json({ error: 'Insufficient permissions' });
        }
        
        try {
            // Get current version from package.json (fallback to version.txt)
            let currentVersion = '1.0.0';
            try {
                const packageJson = JSON.parse(fs.readFileSync(path.join(process.cwd(), 'package.json'), 'utf8'));
                currentVersion = packageJson.version || '1.0.0';
            } catch (e) {
                // Fallback to version.txt if package.json fails
                if (fs.existsSync(path.join(process.cwd(), 'version.txt'))) {
                    currentVersion = fs.readFileSync(path.join(process.cwd(), 'version.txt'), 'utf8').trim();
                }
            }
            
            // Check GitHub for latest release
            const response = await fetch('https://api.github.com/repos/crucifix86/2004scape-server-new/releases/latest', {
                headers: {
                    'User-Agent': '2004scape-server'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to check for updates');
            }
            
            const release = await response.json();
            const latestVersion = release.tag_name?.replace('v', '') || release.name || 'Unknown';
            
            res.json({
                currentVersion,
                latestVersion,
                updateAvailable: latestVersion !== currentVersion && latestVersion !== 'Unknown',
                changelog: release.body || 'No changelog available',
                downloadUrl: release.zipball_url
            });
        } catch (err) {
            console.error('Update check failed:', err);
            res.json({
                currentVersion: 'Unknown',
                latestVersion: 'Unknown',
                updateAvailable: false,
                error: err.message
            });
        }
    });
    
    // Create backup
    app.post('/admin/create-backup', requireAdmin, async (req, res) => {
        if (req.session.user.staffmodlevel < 4) {
            return res.status(403).json({ error: 'Insufficient permissions' });
        }
        
        try {
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const backupName = `backup-${timestamp}.tar.gz`;
            const backupPath = path.join('/home/crucifix', backupName);
            
            // Create backup using tar
            const { exec } = require('child_process');
            const util = require('util');
            const execPromise = util.promisify(exec);
            
            await execPromise(`cd /home/crucifix && tar --exclude='node_modules' --exclude='.git' --exclude='*.log' --exclude='*.pid' -czf ${backupName} 2004scape-server`);
            
            // Log the action (check if table exists first)
            try {
                db.prepare(`
                    INSERT INTO moderator_logs (moderator_id, moderator_name, action, target_name, details, timestamp)
                    VALUES (?, ?, ?, ?, ?, datetime('now'))
                `).run(
                    req.session.user.id,
                    req.session.user.username,
                    'CREATE_BACKUP',
                    'System',
                    `Created backup: ${backupName}`
                );
            } catch (logErr) {
                console.error('Failed to log backup action:', logErr);
                // Continue even if logging fails
            }
            
            res.json({ success: true, filename: backupName });
        } catch (err) {
            console.error('Backup creation failed:', err);
            res.status(500).json({ error: 'Failed to create backup' });
        }
    });
    
    // List backups
    app.get('/admin/list-backups', requireAdmin, async (req, res) => {
        if (req.session.user.staffmodlevel < 4) {
            return res.status(403).json({ error: 'Insufficient permissions' });
        }
        
        try {
            const files = fs.readdirSync('/home/crucifix');
            const backups = files
                .filter(f => (f.startsWith('backup-') || f.startsWith('pre-update-backup-') || f.startsWith('2004scape-backup-')) && f.endsWith('.tar.gz'))
                .map(filename => {
                    const stats = fs.statSync(path.join('/home/crucifix', filename));
                    return {
                        filename,
                        size: (stats.size / 1024 / 1024).toFixed(2) + ' MB',
                        created: stats.mtime.toLocaleString()
                    };
                })
                .sort((a, b) => b.created.localeCompare(a.created));
            
            res.json(backups);
        } catch (err) {
            console.error('Failed to list backups:', err);
            res.json([]);
        }
    });
    
    // Download backup
    app.get('/admin/download-backup/:filename', requireAdmin, (req, res) => {
        if (req.session.user.staffmodlevel < 4) {
            return res.status(403).send('Insufficient permissions');
        }
        
        const filename = req.params.filename;
        
        // Validate filename to prevent directory traversal
        if (!filename.match(/^(backup-|pre-update-backup-|2004scape-backup-)[\d\-TZ]+\.tar\.gz$/)) {
            return res.status(400).send('Invalid backup filename');
        }
        
        const filePath = path.join('/home/crucifix', filename);
        
        if (!fs.existsSync(filePath)) {
            return res.status(404).send('Backup not found');
        }
        
        res.download(filePath);
    });
    
    // Delete backup
    app.delete('/admin/delete-backup/:filename', requireAdmin, (req, res) => {
        if (req.session.user.staffmodlevel < 4) {
            return res.status(403).send('Insufficient permissions');
        }
        
        const filename = req.params.filename;
        
        // Validate filename to prevent directory traversal
        if (!filename.match(/^(backup-|pre-update-backup-|2004scape-backup-)[\d\-TZ]+\.tar\.gz$/)) {
            return res.status(400).send('Invalid backup filename');
        }
        
        const filePath = path.join('/home/crucifix', filename);
        
        if (!fs.existsSync(filePath)) {
            return res.status(404).send('Backup not found');
        }
        
        try {
            fs.unlinkSync(filePath);
            
            // Log the action
            try {
                db.prepare(`
                    INSERT INTO moderator_logs (moderator_id, moderator_name, action, target_name, details, timestamp)
                    VALUES (?, ?, ?, ?, ?, datetime('now'))
                `).run(
                    req.session.user.id,
                    req.session.user.username,
                    'DELETE_BACKUP',
                    'System',
                    `Deleted backup: ${filename}`
                );
            } catch (logErr) {
                console.error('Failed to log delete action:', logErr);
            }
            
            res.json({ success: true });
        } catch (err) {
            console.error('Failed to delete backup:', err);
            res.status(500).send('Failed to delete backup');
        }
    });
    
    // Start update
    app.post('/admin/start-update', requireAdmin, async (req, res) => {
        if (req.session.user.staffmodlevel < 4) {
            return res.status(403).json({ error: 'Insufficient permissions' });
        }
        
        const { exec, spawn } = require('child_process');
        const util = require('util');
        const execPromise = util.promisify(exec);
        const https = require('https');
        
        try {
            const { version } = req.body;
            
            // Create backup first if enabled
            const autoBackup = db.prepare('SELECT value FROM settings WHERE key = ?').get('update_auto_backup')?.value !== 'false';
            
            if (autoBackup) {
                const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                const backupName = `pre-update-backup-${timestamp}.tar.gz`;
                
                await execPromise(`cd /home/crucifix && tar --exclude='node_modules' --exclude='.git' --exclude='*.log' --exclude='*.pid' -czf ${backupName} 2004scape-server`);
            }
            
            // Get exclude settings
            const excludeCss = db.prepare('SELECT value FROM settings WHERE key = ?').get('update_exclude_css')?.value === 'true';
            const excludeIndex = db.prepare('SELECT value FROM settings WHERE key = ?').get('update_exclude_index')?.value === 'true';
            const excludeViews = db.prepare('SELECT value FROM settings WHERE key = ?').get('update_exclude_views')?.value === 'true';
            
            // Log the update action
            try {
                db.prepare(`
                    INSERT INTO moderator_logs (moderator_id, moderator_name, action, target_name, details, timestamp)
                    VALUES (?, ?, ?, ?, ?, datetime('now'))
                `).run(
                    req.session.user.id,
                    req.session.user.username,
                    'SYSTEM_UPDATE',
                    'System',
                    `Started update to version ${version}`
                );
            } catch (logErr) {
                console.error('Failed to log update action:', logErr);
                // Continue even if logging fails
            }
            
            // Download the update from GitHub
            const downloadUrl = `https://api.github.com/repos/crucifix86/2004scape-server-new/zipball/v${version}`;
            const tempDir = `/tmp/2004scape-update-${Date.now()}`;
            const zipPath = `${tempDir}.zip`;
            
            // Create temp directory
            await execPromise(`mkdir -p ${tempDir}`);
            
            // Download the release
            const file = fs.createWriteStream(zipPath);
            await new Promise((resolve, reject) => {
                https.get(downloadUrl, {
                    headers: {
                        'User-Agent': '2004scape-server',
                        'Accept': 'application/vnd.github.v3+json'
                    }
                }, (response) => {
                    if (response.statusCode === 302 || response.statusCode === 301) {
                        // Follow redirect
                        https.get(response.headers.location, (redirectResponse) => {
                            redirectResponse.pipe(file);
                            file.on('finish', () => {
                                file.close();
                                resolve();
                            });
                        }).on('error', reject);
                    } else {
                        response.pipe(file);
                        file.on('finish', () => {
                            file.close();
                            resolve();
                        });
                    }
                }).on('error', reject);
            });
            
            // Extract the downloaded file
            await execPromise(`unzip -q ${zipPath} -d ${tempDir}`);
            
            // Find the extracted directory (GitHub creates a directory with the repo name and commit hash)
            const extractedDirs = fs.readdirSync(tempDir);
            if (extractedDirs.length === 0) {
                throw new Error('No files extracted from update package');
            }
            const extractedDir = path.join(tempDir, extractedDirs[0]);
            
            // Build rsync exclude parameters - CRITICAL: Protect user data!
            let excludeParams = [
                '--exclude=node_modules',
                '--exclude=.git',
                '--exclude=*.log',
                '--exclude=*.pid',
                '--exclude=db.sqlite',
                '--exclude=*.sqlite',
                '--exclude=*.db',
                '--exclude=website/uploads',
                '--exclude=data/players',
                '--exclude=.env',
                '--exclude=server',
                '--exclude=cookies.txt',
                '--exclude=*.backup'
            ];
            if (excludeCss) {
                excludeParams.push('--exclude=website/css');
            }
            if (excludeIndex) {
                excludeParams.push('--exclude=website/index.php');
            }
            if (excludeViews) {
                excludeParams.push('--exclude=website/views');
            }
            
            // Apply the update using rsync WITHOUT --delete to prevent data loss
            const rsyncCommand = `rsync -a ${excludeParams.join(' ')} ${extractedDir}/ /home/crucifix/2004scape-server/`;
            
            // Use spawn instead of exec to handle large outputs
            const rsyncProcess = spawn('rsync', [
                '-a',
                // '--delete' REMOVED to prevent accidental data deletion
                ...excludeParams,
                `${extractedDir}/`,
                '/home/crucifix/2004scape-server/'
            ]);
            
            let rsyncOutput = '';
            let rsyncError = '';
            
            rsyncProcess.stdout.on('data', (data) => {
                rsyncOutput += data.toString();
                // Limit output size
                if (rsyncOutput.length > 10000) {
                    rsyncOutput = rsyncOutput.slice(-5000) + '... (truncated)';
                }
            });
            
            rsyncProcess.stderr.on('data', (data) => {
                rsyncError += data.toString();
            });
            
            await new Promise((resolve, reject) => {
                rsyncProcess.on('close', (code) => {
                    if (code !== 0) {
                        reject(new Error(`rsync failed with code ${code}: ${rsyncError}`));
                    } else {
                        resolve();
                    }
                });
            });
            
            // Clean up temporary files
            await execPromise(`rm -rf ${tempDir} ${zipPath}`);
            
            // Verify critical files still exist
            const criticalFiles = ['db.sqlite', '.env', 'server'];
            const missingFiles = [];
            for (const file of criticalFiles) {
                if (!fs.existsSync(path.join(process.cwd(), file))) {
                    missingFiles.push(file);
                }
            }
            
            if (missingFiles.length > 0) {
                throw new Error(`Critical files missing after update: ${missingFiles.join(', ')}. Please restore from backup!`);
            }
            
            // Update version file
            fs.writeFileSync(path.join(process.cwd(), 'version.txt'), version);
            
            // Update package.json version
            const packageJsonPath = path.join(process.cwd(), 'package.json');
            const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
            packageJson.version = version;
            fs.writeFileSync(packageJsonPath, JSON.stringify(packageJson, null, 2));
            
            // Log successful update
            try {
                db.prepare(`
                    INSERT INTO moderator_logs (moderator_id, moderator_name, action, target_name, details, timestamp)
                    VALUES (?, ?, ?, ?, ?, datetime('now'))
                `).run(
                    req.session.user.id,
                    req.session.user.username,
                    'SYSTEM_UPDATE',
                    'System',
                    `Successfully updated to version ${version}`
                );
            } catch (logErr) {
                console.error('Failed to log update success:', logErr);
            }
            
            // Send success response before server might restart
            res.json({ success: true, message: 'Update applied successfully. Server will restart automatically.' });
            
            // If using tsx watch, the server will auto-restart when files change
            // Otherwise, we could trigger a restart here
        } catch (err) {
            console.error('Update failed:', err);
            res.status(500).json({ error: 'Update failed: ' + err.message });
        }
    });
    
    // Update progress endpoint (SSE)
    app.get('/admin/update-progress', requireAdmin, (req, res) => {
        if (req.session.user.staffmodlevel < 4) {
            return res.status(403).send('Insufficient permissions');
        }
        
        res.writeHead(200, {
            'Content-Type': 'text/event-stream',
            'Cache-Control': 'no-cache',
            'Connection': 'keep-alive'
        });
        
        // In a real implementation, this would send progress updates
        // For now, just send a completion message
        setTimeout(() => {
            res.write(`data: ${JSON.stringify({ progress: 100, status: 'completed' })}\n\n`);
            res.end();
        }, 2000);
    });

    // Admin - Settings
    app.get('/admin/settings', requireAdmin, (req, res) => {
        // Check permission
        if (!req.hasPermission('view_settings')) {
            return res.render('admin/error', {
                user: req.session.user,
                error: 'You do not have permission to view settings.'
            });
        }
        const settings = {};
        const settingsRows = db.prepare('SELECT key, value FROM settings').all();
        settingsRows.forEach(row => {
            settings[row.key] = row.value;
        });
        
        // Get moderator permissions if user is developer
        let permissions = [];
        if (req.session.user.staffmodlevel >= 4) {
            permissions = db.prepare('SELECT * FROM moderator_permissions ORDER BY permission_name').all();
        }
        
        res.render('admin/settings', {
            user: req.session.user,
            settings,
            permissions,
            serverOnline: World && World.gameMap ? true : false
        });
    });

    // Admin - Update permissions (developer only)
    app.post('/admin/permissions', requireAdmin, (req, res) => {
        // Only developers can update permissions
        if (req.session.user.staffmodlevel < 4) {
            return res.redirect('/admin/settings');
        }
        
        const permissions = req.body.permissions || {};
        
        // First, disable all permissions
        db.prepare('UPDATE moderator_permissions SET enabled = 0').run();
        
        // Then enable only the checked ones
        for (const [key, value] of Object.entries(permissions)) {
            if (value === 'on') {
                db.prepare('UPDATE moderator_permissions SET enabled = 1 WHERE permission_key = ?').run(key);
            }
        }
        
        res.redirect('/admin/settings');
    });

    // Admin - Update settings
    app.post('/admin/settings', requireAdmin, (req, res) => {
        // Check if this is a single setting update (JSON) or multiple settings (form data)
        if (req.body.key && typeof req.body.key === 'string') {
            // Single setting update from JavaScript
            const { key, value } = req.body;
            if (value !== undefined && value !== null) {
                db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)').run(key, String(value || ''));
                
                // Update Bob's script when starting gold changes
                if (key === 'starting_gold') {
                    const bobScriptPath = path.join(process.cwd(), 'data/src/scripts/areas/area_lumbridge/scripts/bob.rs2');
                    if (fs.existsSync(bobScriptPath)) {
                        let content = fs.readFileSync(bobScriptPath, 'utf8');
                        content = content.replace(/def_int \$starting_gold = \d+; \/\/ STARTING_GOLD_AMOUNT/, 
                                                `def_int $starting_gold = ${value}; // STARTING_GOLD_AMOUNT`);
                        fs.writeFileSync(bobScriptPath, content);
                    }
                }
            }
            res.json({ success: true });
        } else {
            // Multiple settings from form submission
            const settings = req.body;
            
            // Update each setting in the database
            for (const [key, value] of Object.entries(settings)) {
                if (value !== undefined && value !== null && value !== '') {
                    db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)').run(key, String(value));
                    
                    // Update Bob's script when starting gold changes
                    if (key === 'starting_gold') {
                        const bobScriptPath = path.join(process.cwd(), 'data/src/scripts/areas/area_lumbridge/scripts/bob.rs2');
                        if (fs.existsSync(bobScriptPath)) {
                            let content = fs.readFileSync(bobScriptPath, 'utf8');
                            content = content.replace(/def_int \$starting_gold = \d+; \/\/ STARTING_GOLD_AMOUNT/, 
                                                    `def_int $starting_gold = ${value}; // STARTING_GOLD_AMOUNT`);
                            fs.writeFileSync(bobScriptPath, content);
                        }
                    }
                }
            }
            
            res.redirect('/admin/settings');
        }
    });

    // Admin - News management
    app.get('/admin/news', requireAdmin, (req, res) => {
        // Check permission
        if (!req.hasPermission('manage_news')) {
            return res.render('admin/error', {
                user: req.session.user,
                error: 'You do not have permission to manage news.'
            });
        }
        const news = db.prepare('SELECT * FROM news ORDER BY created_at DESC').all();
        
        res.render('admin/news', {
            user: req.session.user,
            news
        });
    });

    // Admin - Restart server (Developers only)
    app.post('/admin/restart_server', (req, res, next) => {
        // Check if user is a developer (staffmodlevel >= 4)
        if (!req.session.user || req.session.user.staffmodlevel < 4) {
            return res.status(403).json({ error: 'Insufficient permissions' });
        }
        next();
    }, (req, res) => {
        try {
            // Send response before restarting
            res.json({ success: true, message: 'Server restart initiated' });
            
            // Use spawn with detached option to survive parent process termination
            setTimeout(() => {
                const { spawn } = require('child_process');
                const restart = spawn('./server', ['restart'], {
                    detached: true,
                    stdio: 'ignore'
                });
                restart.unref(); // Allow parent to exit independently
            }, 100);
        } catch (err) {
            res.status(500).json({ error: 'Failed to restart server' });
        }
    });

    // Admin - Add news
    app.post('/admin/news/add', requireAdmin, (req, res) => {
        const { title, content } = req.body;
        
        db.prepare(`
            INSERT INTO news (title, content, author_id, created_at, updated_at)
            VALUES (?, ?, ?, datetime('now'), datetime('now'))
        `).run(title, content, req.session.user.id);
        
        res.redirect('/admin/news');
    });

    // Admin - Edit news
    app.post('/admin/news/edit', requireAdmin, (req, res) => {
        const { news_id, title, content, is_active } = req.body;
        
        db.prepare(`
            UPDATE news 
            SET title = ?, content = ?, is_active = ?, updated_at = datetime('now')
            WHERE id = ?
        `).run(title, content, parseInt(is_active), news_id);
        
        // Log the action
        db.prepare(`
            INSERT INTO mod_action (account_id, action, reason)
            VALUES (?, 'edit_news', ?)
        `).run(req.session.user.id, `Edited news article: ${title}`);
        
        res.redirect('/admin/news');
    });

    // Admin - Delete news
    app.post('/admin/news/delete', requireAdmin, (req, res) => {
        const { news_id } = req.body;
        
        db.prepare('DELETE FROM news WHERE id = ?').run(news_id);
        
        res.redirect('/admin/news');
    });

    // Admin - Unban player
    app.post('/admin/unban', requireAdmin, (req, res) => {
        const { player_id } = req.body;
        
        db.prepare('UPDATE account SET banned_until = NULL WHERE id = ?').run(player_id);
        
        // Log the action
        db.prepare(`
            INSERT INTO mod_action (account_id, target_id, action, reason)
            VALUES (?, ?, 'unban', 'Manual unban')
        `).run(req.session.user.id, player_id);
        
        res.redirect('/admin/bans');
    });

    // Admin - Unmute player
    app.post('/admin/unmute', requireAdmin, (req, res) => {
        const { player_id } = req.body;
        
        db.prepare('UPDATE account SET muted_until = NULL WHERE id = ?').run(player_id);
        
        // Log the action
        db.prepare(`
            INSERT INTO mod_action (account_id, target_id, action, reason)
            VALUES (?, ?, 'unmute', 'Manual unmute')
        `).run(req.session.user.id, player_id);
        
        res.redirect('/admin/bans');
    });

    // Admin - Promote/Demote player
    app.post('/admin/promote', requireAdmin, (req, res) => {
        const { player_id, staffmodlevel } = req.body;
        const newLevel = parseInt(staffmodlevel);
        
        // Check permissions
        if (req.session.user.staffmodlevel < 3) {
            return res.redirect('/admin/players');
        }
        
        // Only developers can promote to admin/developer
        if (newLevel >= 3 && req.session.user.staffmodlevel < 4) {
            return res.redirect('/admin/players');
        }
        
        // Get target player info
        const target = db.prepare('SELECT username FROM account WHERE id = ?').get(player_id);
        if (!target) {
            return res.redirect('/admin/players');
        }
        
        // Update the rank
        db.prepare('UPDATE account SET staffmodlevel = ? WHERE id = ?').run(newLevel, player_id);
        
        // Log the action
        const rankNames = ['Player', 'Helper', 'Moderator', 'Admin', 'Developer'];
        db.prepare(`
            INSERT INTO mod_action (account_id, target_id, action, reason)
            VALUES (?, ?, 'promote', ?)
        `).run(req.session.user.id, player_id, `Changed rank to ${rankNames[newLevel]}`);
        
        res.redirect('/admin/players');
    });

    // Admin - Delete account (Developers only)
    app.post('/admin/delete_account', requireAdmin, (req, res) => {
        const { player_id, confirm } = req.body;
        
        // Only developers can delete accounts
        if (req.session.user.staffmodlevel < 4) {
            return res.redirect('/admin/players');
        }
        
        // Check confirmation
        if (confirm !== 'DELETE') {
            return res.redirect('/admin/players');
        }
        
        // Can't delete your own account
        if (parseInt(player_id) === req.session.user.id) {
            return res.redirect('/admin/players');
        }
        
        // Get account info for logging
        const target = db.prepare('SELECT username FROM account WHERE id = ?').get(player_id);
        if (!target) {
            return res.redirect('/admin/players');
        }
        
        // Delete the account
        db.prepare('DELETE FROM account WHERE id = ?').run(player_id);
        
        // Delete related records
        db.prepare('DELETE FROM login WHERE account_id = ?').run(player_id);
        db.prepare('DELETE FROM session WHERE account_id = ?').run(player_id);
        
        // Delete save file if exists
        const savePath = path.join(process.cwd(), 'data/players/main', `${target.username}.sav`);
        if (fs.existsSync(savePath)) {
            fs.unlinkSync(savePath);
        }
        
        // Log the action
        db.prepare(`
            INSERT INTO mod_action (account_id, target_id, action, reason)
            VALUES (?, ?, 'delete_account', ?)
        `).run(req.session.user.id, player_id, `Deleted account: ${target.username}`);
        
        res.redirect('/admin/players');
    });

    // Admin - Create account
    app.post('/admin/create_account', requireAdmin, (req, res) => {
        const { username, password, email, staffmodlevel } = req.body;
        const newLevel = parseInt(staffmodlevel || '0');
        
        // Check permissions
        if (req.session.user.staffmodlevel < 3) {
            return res.redirect('/admin/players');
        }
        
        // Only developers can create admin/developer accounts
        if (newLevel >= 3 && req.session.user.staffmodlevel < 4) {
            return res.redirect('/admin/players');
        }
        
        // Validate input
        if (!username || !password || !email) {
            return res.redirect('/admin/players');
        }
        
        if (username.length < 3 || username.length > 12 || !/^[a-zA-Z0-9_]+$/.test(username)) {
            return res.redirect('/admin/players');
        }
        
        // Check if username exists
        const existing = db.prepare('SELECT id FROM account WHERE LOWER(username) = LOWER(?)').get(username);
        if (existing) {
            return res.redirect('/admin/players');
        }
        
        // Create account
        const hashedPassword = bcrypt.hashSync(password.toLowerCase(), 10);
        const ip = req.ip || req.connection.remoteAddress;
        
        try {
            const result = db.prepare(`
                INSERT INTO account (username, password, email, staffmodlevel, registration_ip, registration_date)
                VALUES (?, ?, ?, ?, ?, datetime('now'))
            `).run(username, hashedPassword, email, newLevel, ip);
            
            // Log the action
            const rankNames = ['Player', 'Helper', 'Moderator', 'Admin', 'Developer'];
            db.prepare(`
                INSERT INTO mod_action (account_id, action, reason)
                VALUES (?, 'create_account', ?)
            `).run(req.session.user.id, `Created account: ${username} (${rankNames[newLevel]})`);
            
            res.redirect('/admin/players');
        } catch (err) {
            res.redirect('/admin/players');
        }
    });

    // Admin - Content Management System
    app.get('/admin/content', requireAdmin, (req, res) => {
        // Check permission
        if (!req.hasPermission('manage_content')) {
            return res.render('admin/error', {
                user: req.session.user,
                error: 'You do not have permission to manage content.'
            });
        }
        // Get site content from database
        const content = {};
        try {
            const contentRows = db.prepare('SELECT key, value FROM site_content').all();
            contentRows.forEach(row => {
                content[row.key] = row.value;
            });
        } catch (err) {
            // Table might not exist, create it
            db.exec(`
                CREATE TABLE IF NOT EXISTS site_content (
                    key TEXT PRIMARY KEY,
                    value TEXT NOT NULL
                )
            `);
        }
        
        res.render('admin/content', {
            user: req.session.user,
            content
        });
    });

    // Admin - Update content
    app.post('/admin/content/update', requireAdmin, (req, res) => {
        const { type, key, value } = req.body;
        
        if (type === 'content') {
            db.prepare('INSERT OR REPLACE INTO site_content (key, value) VALUES (?, ?)').run(key, value);
        }
        
        res.json({ success: true });
    });

    // Admin - Save general content
    app.post('/admin/content/general', requireAdmin, (req, res) => {
        const content = req.body;
        
        // Save each content item
        Object.keys(content).forEach(key => {
            db.prepare('INSERT OR REPLACE INTO site_content (key, value) VALUES (?, ?)').run(key, content[key]);
        });
        
        res.json({ success: true });
    });
    
    // Admin - Update feature cards
    app.post('/admin/content/features', requireAdmin, (req, res) => {
        const features = req.body.features;
        
        // Store features as JSON
        db.prepare('INSERT OR REPLACE INTO site_content (key, value) VALUES (?, ?)').run('features', JSON.stringify(features));
        
        res.redirect('/admin/content?success=Features updated');
    });
    
    // Admin - Upload logo (developer only)
    app.post('/admin/content/logo', requireAdmin, upload.single('logo'), (req, res) => {
        // Only developers can upload logo
        if (req.session.user.staffmodlevel < 4) {
            return res.status(403).json({ error: 'Insufficient permissions' });
        }
        
        if (!req.file) {
            return res.status(400).json({ error: 'No file uploaded' });
        }
        
        // Save logo path to database
        const logoPath = '/uploads/' + req.file.filename;
        db.prepare('INSERT OR REPLACE INTO site_content (key, value) VALUES (?, ?)').run('site_logo', logoPath);
        
        res.json({ success: true, path: logoPath });
    });
    
    // Admin - Remove logo (developer only)
    app.delete('/admin/content/logo', requireAdmin, (req, res) => {
        // Only developers can remove logo
        if (req.session.user.staffmodlevel < 4) {
            return res.status(403).json({ error: 'Insufficient permissions' });
        }
        
        // Get current logo
        const logo = db.prepare('SELECT value FROM site_content WHERE key = ?').get('site_logo');
        if (logo) {
            // Delete file
            const filePath = path.join(__dirname, '../../website', logo.value);
            if (fs.existsSync(filePath)) {
                fs.unlinkSync(filePath);
            }
        }
        
        // Remove from database
        db.prepare('DELETE FROM site_content WHERE key = ?').run('site_logo');
        
        res.json({ success: true });
    });

    return app;
}