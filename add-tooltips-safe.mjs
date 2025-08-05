import fs from 'fs';
import { minify } from 'terser';

// Read the beautified client
let client = fs.readFileSync('public/client/client-beautified.js', 'utf8');

// Find where B.draw(u, j) is called for inventory items
// This is around line 9987 in the beautified version
// Let's add tooltip tracking more carefully

// First, find the exact location where items are drawn
const itemDrawSection = client.indexOf('else B.draw(u, j);');
if (itemDrawSection === -1) {
    console.error('Could not find item draw section!');
    process.exit(1);
}

// Add a simple tooltip display after the item is drawn
const tooltipTracking = `
                                // Simple tooltip on hover
                                if (this.mouseX >= u && this.mouseX < u + 32 && this.mouseY >= j && this.mouseY < j + 32) {
                                    // Draw simple tooltip with item ID
                                    let tooltipX = this.mouseX + 10;
                                    let tooltipY = this.mouseY - 20;
                                    if (tooltipX + 100 > 512) tooltipX = this.mouseX - 110;
                                    if (tooltipY < 0) tooltipY = this.mouseY + 20;
                                    
                                    // Draw tooltip background
                                    T.fillRect2d(tooltipX, tooltipY, 100, 20, 0x000000);
                                    T.drawRect(tooltipX, tooltipY, 100, 20, 0xFFFF00);
                                    
                                    // Draw item info
                                    if (this.fontPlain11) {
                                        let itemName = "Item #" + X;
                                        // Special names for common items
                                        if (X === 995) itemName = "Coins";
                                        else if (X === 1038) itemName = "Red partyhat";
                                        else if (X === 1040) itemName = "Yellow partyhat";
                                        else if (X === 1042) itemName = "Blue partyhat";
                                        else if (X === 1044) itemName = "Green partyhat";
                                        else if (X === 1046) itemName = "Purple partyhat";
                                        else if (X === 1048) itemName = "White partyhat";
                                        else if (X === 4151) itemName = "Abyssal whip";
                                        else if (X === 1163) itemName = "Rune full helm";
                                        else if (X === 1127) itemName = "Rune platebody";
                                        else if (X === 1079) itemName = "Rune platelegs";
                                        else if (X === 1333) itemName = "Rune scimitar";
                                        else if (X === 554) itemName = "Fire rune";
                                        else if (X === 555) itemName = "Water rune";
                                        else if (X === 556) itemName = "Air rune";
                                        else if (X === 557) itemName = "Earth rune";
                                        else if (X === 560) itemName = "Death rune";
                                        else if (X === 561) itemName = "Nature rune";
                                        else if (X === 562) itemName = "Chaos rune";
                                        else if (X === 563) itemName = "Law rune";
                                        
                                        this.fontPlain11.drawString(tooltipX + 3, tooltipY + 13, itemName, 0xFFFF00);
                                    }
                                }`;

// Insert the tooltip code right after B.draw(u, j)
const beforeTooltip = client.substring(0, itemDrawSection + 18); // "else B.draw(u, j);" is 18 chars
const afterTooltip = client.substring(itemDrawSection + 18);
client = beforeTooltip + tooltipTracking + afterTooltip;

// Write the modified beautified version for debugging
fs.writeFileSync('public/client/client-tooltips-safe.js', client);

// Now minify it
async function minifyClient() {
    try {
        const result = await minify(client, {
            compress: {
                drop_console: false,
                drop_debugger: false,
                dead_code: false,
                conditionals: false,
                evaluate: false,
                booleans: false,
                loops: false,
                unused: false,
                if_return: false,
                join_vars: false
            },
            mangle: {
                keep_fnames: true
            },
            format: {
                beautify: false,
                comments: false
            }
        });
        
        fs.writeFileSync('public/client/client.js', result.code);
        console.log('Client modified with tooltips successfully!');
        console.log('Tooltips will show when hovering over items in inventory.');
    } catch (err) {
        console.error('Minification error:', err.message);
        console.log('Trying alternative approach...');
        
        // Try a simpler minification
        try {
            const simpleResult = await minify(client, {
                compress: false,
                mangle: false
            });
            fs.writeFileSync('public/client/client.js', simpleResult.code);
            console.log('Client modified with tooltips (simple minification)!');
        } catch (err2) {
            console.error('Simple minification also failed:', err2.message);
            // As last resort, use the beautified version
            fs.writeFileSync('public/client/client.js', client);
            console.log('Client modified with tooltips (unminified)!');
        }
    }
}

minifyClient();