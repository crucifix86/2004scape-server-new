import Database from 'better-sqlite3';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Use the same database as the website
const dbPath = path.join(__dirname, '../../db.sqlite');
const db = new Database(dbPath);

// Ensure chat_log table exists
db.exec(`
    CREATE TABLE IF NOT EXISTS chat_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        account_id INTEGER NOT NULL,
        username TEXT NOT NULL,
        message TEXT NOT NULL,
        chat_type TEXT NOT NULL,
        target_username TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )
`);

export function logChat(account_id: number, username: string, message: string, chat_type: string, target_username?: string) {
    try {
        db.prepare(`
            INSERT INTO chat_log (account_id, username, message, chat_type, target_username)
            VALUES (?, ?, ?, ?, ?)
        `).run(account_id, username, message, chat_type, target_username || null);
    } catch (err) {
        console.error('Failed to log chat message:', err);
    }
}

export default db;