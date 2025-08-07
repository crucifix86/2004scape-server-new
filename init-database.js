const Database = require('better-sqlite3');
const bcrypt = require('bcrypt');
const fs = require('fs');

// Get command line arguments
const username = process.argv[2];
const password = process.argv[3];

if (!username || !password) {
    console.error('Usage: node init-database.js <username> <password>');
    process.exit(1);
}

console.log('Initializing database...');

const db = new Database('db.sqlite');

// Create account table
db.exec(`
    CREATE TABLE IF NOT EXISTS account (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT,
        registration_ip TEXT,
        registration_date DATETIME,
        staffmodlevel INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
    try {
        db.exec(`ALTER TABLE hiscores ADD COLUMN ${skill}_xp INTEGER DEFAULT 0`);
        db.exec(`ALTER TABLE hiscores ADD COLUMN ${skill}_level INTEGER DEFAULT 1`);
    } catch (e) {
        // Column might already exist, that's okay
    }
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
const hashedPassword = bcrypt.hashSync(password.toLowerCase(), 10);
try {
    db.prepare(`
        INSERT INTO account (username, password, email, registration_ip, registration_date, staffmodlevel)
        VALUES (?, ?, ?, ?, datetime('now'), ?)
    `).run(username, hashedPassword, 'admin@2004scape.com', '127.0.0.1', 2);
    console.log(`Created developer account: ${username}`);
} catch (e) {
    if (e.message.includes('UNIQUE constraint failed')) {
        console.log(`Account ${username} already exists`);
    } else {
        throw e;
    }
}

// Add to hiscores
const accountId = db.prepare('SELECT id FROM account WHERE username = ?').get(username).id;
try {
    db.prepare('INSERT INTO hiscores (account_id, username, rights) VALUES (?, ?, ?)').run(accountId, username, 2);
} catch (e) {
    // Might already exist
}

console.log('Database initialized successfully');
db.close();