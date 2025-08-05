const fs = require('fs');

// Read the beautified client
let client = fs.readFileSync('public/client/client-beautified.js', 'utf8');

// Add the sparkle effect rendering function after items are drawn
// Find the item drawing section and add effects
const itemDrawPattern = /(\s+else B\.draw\(u, j\);)/;

// Insert sparkle effect code after items are drawn
const sparkleCode = `$1
                                // Add visual effects for valuable items
                                if ($.invSlotObjId[K] > 0) {
                                    // Get item value from the item ID (simplified check for now)
                                    let itemValue = X * 100; // Rough estimate based on item ID
                                    
                                    // Add sparkle effect for valuable items
                                    if (itemValue > 10000) {
                                        // Draw a sparkle overlay
                                        let sparkleFrame = (Date.now() / 100 | 0) % 8;
                                        let sparkleX = u + 8 + Math.sin(sparkleFrame * 0.8) * 4;
                                        let sparkleY = j + 8 + Math.cos(sparkleFrame * 0.8) * 4;
                                        
                                        // Draw sparkle points
                                        T.fillRect2d(sparkleX, sparkleY, 2, 2, 0xFFFF00);
                                        T.fillRect2d(sparkleX + 8, sparkleY + 8, 2, 2, 0xFFFF00);
                                        T.fillRect2d(sparkleX - 8, sparkleY + 8, 2, 2, 0xFFFF00);
                                        T.fillRect2d(sparkleX + 8, sparkleY - 8, 2, 2, 0xFFFF00);
                                        
                                        // Add glow effect for very valuable items
                                        if (itemValue > 50000) {
                                            // Draw a subtle glow box around the item
                                            for (let i = 0; i < 3; i++) {
                                                let alpha = 64 - i * 20;
                                                let color = 0xFFD700; // Gold color
                                                if (itemValue > 100000) color = 0xFF00FF; // Purple for ultra-rare
                                                
                                                // Draw glow outline
                                                T.drawRect(u - i, j - i, 32 + i * 2, 32 + i * 2, color);
                                            }
                                        }
                                    } else if (itemValue > 1000) {
                                        // Subtle shimmer for mid-value items
                                        let shimmerFrame = (Date.now() / 200 | 0) % 4;
                                        if (shimmerFrame === 0) {
                                            T.fillRect2d(u + 14, j + 14, 4, 4, 0xCCCCCC);
                                        }
                                    }
                                }`;

// Replace the pattern
client = client.replace(itemDrawPattern, sparkleCode);

// Also add effect when items are being dragged
const dragDrawPattern = /(B\.drawAlpha\(128, u \+ m, j \+ Y\))/g;
const dragSparkleCode = `$1;
                                    // Add sparkle to dragged valuable items
                                    let dragItemValue = X * 100;
                                    if (dragItemValue > 10000) {
                                        let sparkleFrame = (Date.now() / 100 | 0) % 8;
                                        let sparkleX = u + m + 8 + Math.sin(sparkleFrame * 0.8) * 4;
                                        let sparkleY = j + Y + 8 + Math.cos(sparkleFrame * 0.8) * 4;
                                        T.fillRect2d(sparkleX, sparkleY, 2, 2, 0xFFFF00);
                                    }`;

client = client.replace(dragDrawPattern, dragSparkleCode);

// Write the modified beautified version
fs.writeFileSync('public/client/client-effects.js', client);

// Now minify it back
const { minify } = require('terser');

async function minifyClient() {
    const result = await minify(client, {
        compress: {
            drop_console: false,
            drop_debugger: false
        },
        mangle: true
    });
    
    fs.writeFileSync('public/client/client.js', result.code);
    console.log('Client modified with visual effects!');
}

minifyClient().catch(err => {
    console.error('Error minifying:', err);
    // If minification fails, use the beautified version
    fs.writeFileSync('public/client/client.js', client);
    console.log('Client modified with visual effects (unminified)!');
});