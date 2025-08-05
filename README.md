<div align="center">
    <h1>Lost City - May 18, 2004</h1>
</div>

> [!IMPORTANT]  
> We are changing to [LostCityRS/Server](https://github.com/LostCityRS/Server) for future updates. This repo will remain up as an archive to look back on. We have moved away from a monorepo for the engine and content here.

> [!NOTE]
> Learn about our history and ethos on our forum: https://lostcity.rs/t/faq-what-is-lost-city/16

## Getting Started

> [!IMPORTANT]
> If you run into issues, please see our [common issues](#common-issues).

1. Download and extract this repo somewhere on your computer.
2. Install our [dependencies](#dependencies).
3. Open the folder you downloaded: **Run the quickstart script and follow the on-screen prompts.** You may disregard any severity warnings you see.

Once your setup process has completed, wait for it to tell you the world has started before trying to play.

The server includes its own web client, so you don't have to download a client!

## Dependencies

- [NodeJS 22](https://nodejs.org/)
- [Java 17](https://adoptium.net/) - later LTS versions are also fine.

> [!TIP]
> If you're using VS Code (recommended), [we have an extension to install on the marketplace.](https://marketplace.visualstudio.com/items?itemName=2004scape.runescriptlanguage)

## Workflow

Content developers should run `npm start`. The server will watch for changes to scripts and configs, then automatically repack everything.

Engine developers should run `npm run dev`. This does what `npm start` does above, but also completely restarts the server when engine code has changed.

## Server Management

Use the `./server` script to manage both game and login servers:

- **./server start** - Starts both the game server (port 8888) and login server (port 43500)
- **./server stop** - Stops both servers cleanly
- **./server restart** - Restarts both servers
- **./server status** - Shows current status of both servers

The script handles port conflicts automatically by running the login server's management interface on port 8899.

## Developer Commands

Developer access is controlled via the `data/developers.txt` file. Add usernames (one per line) to grant developer privileges.

Available developer commands (use :: prefix in game chat):
- **::testdev** - Shows your current developer access level
- **::devcoins** - Adds 100,000 coins to your inventory
- **::devfood** - Adds 20 sharks to your inventory  
- **::devrune** - Completes Dragon Slayer quest, sets combat stats to level 40, and adds full rune armor set
- **::resetstats** - Resets all combat stats back to level 1 (HP to 10)
- **::bank** - Opens the bank interface from anywhere
- **::banksearch <item name>** - Searches your bank for items matching the name
- **::depositall** - Deposits all items from inventory to bank

Note: These commands require developer access level 2 or higher.

## Common Issues

* `bad option: --import`  

You are using an older version of Node. Reinstall and re-run.

* `'"java"' is not recognized as an internal or external command`  

You do not have Java installed.

* `XXXXX has been compiled by a more recent version of the Java Runtime (class file version 61.0), this version of the Java Runtime only recognizes class file versions up to 52.0`  

You are using Java 8 or Java 11. If you have multiple Java versions, you will need to set `JAVA_PATH=path-to-java.exe` in your .env file.

## License
This project is licensed under the [MIT License](https://opensource.org/licenses/MIT). See the [LICENSE](LICENSE) file for details.
