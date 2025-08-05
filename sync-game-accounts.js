const Database = require('better-sqlite3');
const bcrypt = require('bcrypt');
const fs = require('fs');
const path = require('path');

// Open database
const db = new Database('db.sqlite');

// Create a test account that matches what the game expects
const username = 'crucifix';
const password = 'test'; // Change this to your actual password
const hashedPassword = bcrypt.hashSync(password.toLowerCase(), 10);

// Check if account exists
const existing = db.prepare('SELECT * FROM account WHERE username = ?').get(username);

if (existing) {
    console.log(`Account ${username} already exists`);
    console.log('Current account details:', {
        id: existing.id,
        username: existing.username,
        staffmodlevel: existing.staffmodlevel
    });
} else {
    // Insert account
    const stmt = db.prepare(`
        INSERT INTO account (username, password, email, registration_ip, registration_date, staffmodlevel)
        VALUES (?, ?, ?, ?, datetime('now'), ?)
    `);
    
    stmt.run(username, hashedPassword, 'admin@2004scape.com', '127.0.0.1', 2);
    console.log(`Created account: ${username} with developer access`);
}

// Also check if developers.txt has this user
const devFile = path.join(__dirname, 'data/developers.txt');
if (fs.existsSync(devFile)) {
    const devs = fs.readFileSync(devFile, 'utf8');
    if (!devs.toLowerCase().includes(username.toLowerCase())) {
        fs.appendFileSync(devFile, `\n${username}\n`);
        console.log(`Added ${username} to developers.txt`);
    }
}

db.close();
console.log('Done!');