import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import Database from 'better-sqlite3';

import { collectDefaultMetrics, register } from 'prom-client';

import { packClient, packServer } from '#/cache/PackAll.js';
import World from '#/engine/World.js';
import TcpServer from '#/server/tcp/TcpServer.js';
import WSServer from '#/server/ws/WSServer.js';
import Environment from '#/util/Environment.js';
import { printError, printInfo } from '#/util/Logger.js';
import { updateCompiler } from '#/util/RuneScriptCompiler.js';
import { createWorker } from '#/util/WorkerFactory.js';
import { startManagementWeb, startWeb, web } from '#/web.js';

if (Environment.BUILD_STARTUP_UPDATE) {
    await updateCompiler();
}

// Load settings from database
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const dbPath = path.join(__dirname, '../db.sqlite');

if (fs.existsSync(dbPath)) {
    try {
        const db = new Database(dbPath);
        const settings = db.prepare('SELECT key, value FROM settings').all();
        
        for (const setting of settings) {
            if (setting.key === 'xp_rate') {
                const xpRate = parseInt(setting.value) || 1;
                Environment.NODE_XPRATE = xpRate;
                printInfo(`XP Rate set to ${xpRate}x from database`);
            } else if (setting.key === 'drop_rate') {
                // Store for future use when drop system is implemented
                const dropRate = parseInt(setting.value) || 1;
                // Environment.NODE_DROPRATE = dropRate; // Will need to add this to Environment.ts
                printInfo(`Drop Rate setting loaded: ${dropRate}x (not yet implemented)`);
            } else if (setting.key === 'starting_gold') {
                const startingGold = parseInt(setting.value) || 0;
                Environment.STARTING_GOLD = startingGold;
                printInfo(`Starting gold set to ${startingGold} from database`);
            } else if (setting.key === 'shop_prices') {
                Environment.SHOP_PRICES = setting.value || 'normal';
                printInfo(`Shop prices set to ${setting.value} from database`);
            } else if (setting.key === 'allow_registration') {
                Environment.ALLOW_REGISTRATION = setting.value === 'true';
                printInfo(`Registration ${setting.value === 'true' ? 'enabled' : 'disabled'} from database`);
            } else if (setting.key === 'max_players') {
                Environment.NODE_MAX_PLAYERS = parseInt(setting.value) || 2000;
                printInfo(`Max players set to ${setting.value} from database`);
            }
        }
        
        db.close();
    } catch (err) {
        printError('Failed to load settings from database: ' + err);
    }
}

if (!fs.existsSync('data/pack/client/config') || !fs.existsSync('data/pack/server/script.dat')) {
    printInfo('Packing cache, please wait until you see the world is ready.');

    try {
        await packServer();
        await packClient();
    } catch (err) {
        if (err instanceof Error) {
            printError(err.message);
        }

        process.exit(1);
    }
}

if (Environment.EASY_STARTUP) {
    createWorker('./login.ts');
    createWorker('./friend.ts');
    createWorker('./logger.ts');
}

await World.start();

const tcpServer = new TcpServer();
tcpServer.start();

const wsServer = new WSServer();
wsServer.start(web);

startWeb();
startManagementWeb();

register.setDefaultLabels({ nodeId: Environment.NODE_ID });
collectDefaultMetrics({ register });

// unfortunately, tsx watch is not giving us a way to gracefully shut down in our dev mode:
// https://github.com/privatenumber/tsx/issues/494
let exiting = false;
function safeExit() {
    if (exiting) {
        return;
    }

    exiting = true;
    World.rebootTimer(0);
}

process.on('SIGINT', safeExit);
process.on('SIGTERM', safeExit);
