import fs from 'fs';
import { minify } from 'terser';

// Read the beautified client
let client = fs.readFileSync('public/client/client-beautified.js', 'utf8');

// Find where mouse position is tracked over inventory items
// We need to add tooltip rendering after items are drawn

// First, let's add a tooltip rendering function
const tooltipFunction = `
    // Item tooltip rendering
    drawItemTooltip(itemId, x, y) {
        if (!itemId || itemId <= 0) return;
        
        // Get item name (simplified - using ID as placeholder)
        let itemName = "Item #" + itemId;
        let itemValue = itemId * 100; // Simplified value calculation
        
        // Common item names (hardcoded for demo)
        const itemNames = {
            1038: "Red partyhat",
            1040: "Yellow partyhat", 
            1042: "Blue partyhat",
            1044: "Green partyhat",
            1046: "Purple partyhat",
            1048: "White partyhat",
            1050: "Christmas cracker",
            1053: "Green h'ween mask",
            1055: "Blue h'ween mask",
            1057: "Red h'ween mask",
            4151: "Abyssal whip",
            11694: "Armadyl godsword",
            11696: "Bandos godsword",
            11698: "Saradomin godsword",
            11700: "Zamorak godsword",
            1163: "Rune full helm",
            1127: "Rune platebody",
            1079: "Rune platelegs",
            1201: "Rune kiteshield",
            1333: "Rune scimitar",
            1359: "Rune axe",
            1373: "Rune battleaxe",
            1434: "Dragon mace",
            1249: "Dragon spear",
            1215: "Dragon dagger",
            1305: "Dragon longsword",
            1377: "Dragon battleaxe",
            1187: "Dragon sq shield",
            3140: "Dragon chainbody",
            4087: "Dragon platelegs",
            4585: "Dragon plateskirt",
            4587: "Dragon scimitar",
            11335: "Dragon full helm",
            1704: "Amulet of glory",
            1712: "Amulet of glory(4)",
            1615: "Dragonstone",
            1631: "Uncut dragonstone",
            995: "Coins",
            554: "Fire rune",
            555: "Water rune", 
            556: "Air rune",
            557: "Earth rune",
            558: "Mind rune",
            559: "Body rune",
            560: "Death rune",
            561: "Nature rune",
            562: "Chaos rune",
            563: "Law rune",
            564: "Cosmic rune",
            565: "Blood rune",
            566: "Soul rune"
        };
        
        if (itemNames[itemId]) {
            itemName = itemNames[itemId];
        }
        
        // Format value with commas
        let valueStr = itemValue.toLocaleString();
        
        // Tooltip background dimensions
        let tooltipWidth = 150;
        let tooltipHeight = 45;
        
        // Adjust position to keep tooltip on screen
        if (x + tooltipWidth > 512) x = 512 - tooltipWidth;
        if (y + tooltipHeight > 334) y = 334 - tooltipHeight;
        if (x < 0) x = 0;
        if (y < 0) y = 0;
        
        // Draw tooltip background
        T.fillRect2d(x, y, tooltipWidth, tooltipHeight, 0x000000);
        T.drawRect(x, y, tooltipWidth, tooltipHeight, 0xFFFF00);
        
        // Draw item info
        if (this.fontBold12) {
            this.fontBold12.drawString(x + 5, y + 15, itemName, 0xFFFF00);
        }
        if (this.fontPlain11) {
            this.fontPlain11.drawString(x + 5, y + 30, "Value: " + valueStr + " gp", 0xFFFFFF);
            
            // Add rarity indicator
            let rarity = "Common";
            let rarityColor = 0x808080;
            if (itemValue > 100000) {
                rarity = "Ultra Rare";
                rarityColor = 0xFF00FF;
            } else if (itemValue > 50000) {
                rarity = "Very Rare";
                rarityColor = 0xFFD700;
            } else if (itemValue > 10000) {
                rarity = "Rare";
                rarityColor = 0x00FFFF;
            } else if (itemValue > 1000) {
                rarity = "Uncommon";
                rarityColor = 0x00FF00;
            }
            this.fontPlain11.drawString(x + 5, y + 42, rarity, rarityColor);
        }
    }
`;

// Insert the tooltip function before the drawInterface method
const drawInterfacePattern = /(\s+drawInterface\()/;
client = client.replace(drawInterfacePattern, tooltipFunction + '\n$1');

// Now modify the inventory drawing to track mouse hover and show tooltips
const invDrawPattern = /(else B\.draw\(u, j\);)([\s\S]*?)(\} else if \(K < 20\))/;

const tooltipCode = `$1
                                // Track mouse hover for tooltips
                                if (this.mouseX >= u && this.mouseX < u + 32 && 
                                    this.mouseY >= j && this.mouseY < j + 32) {
                                    // Store hovered item for tooltip
                                    this.hoveredItemId = $.invSlotObjId[K];
                                    this.hoveredItemX = u + 35;
                                    this.hoveredItemY = j;
                                }$2$3`;

client = client.replace(invDrawPattern, tooltipCode);

// Add tooltip rendering at the end of the draw cycle
const drawTooltipPattern = /(drawTooltip\(\) \{)/;
const enhancedTooltip = `$1
        // Draw item tooltip if hovering
        if (this.hoveredItemId && this.hoveredItemId > 0) {
            this.drawItemTooltip(this.hoveredItemId, this.hoveredItemX, this.hoveredItemY);
            this.hoveredItemId = 0; // Reset for next frame
        }
        `;

client = client.replace(drawTooltipPattern, enhancedTooltip);

// Initialize the hover tracking variables
const initPattern = /(constructor\(\) \{[\s\S]*?)(this\.mouseX = 0)/;
client = client.replace(initPattern, '$1this.hoveredItemId = 0;\n        this.hoveredItemX = 0;\n        this.hoveredItemY = 0;\n        $2');

// Write the modified beautified version
fs.writeFileSync('public/client/client-tooltips.js', client);

// Now minify it back
async function minifyClient() {
    try {
        const result = await minify(client, {
            compress: {
                drop_console: false,
                drop_debugger: false
            },
            mangle: true
        });
        
        fs.writeFileSync('public/client/client.js', result.code);
        console.log('Client modified with item tooltips!');
    } catch (err) {
        console.error('Error minifying:', err);
        // If minification fails, use the beautified version
        fs.writeFileSync('public/client/client.js', client);
        console.log('Client modified with item tooltips (unminified)!');
    }
}

minifyClient();