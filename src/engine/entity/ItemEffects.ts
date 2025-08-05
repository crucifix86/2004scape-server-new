/**
 * Item Visual Effects System
 * Adds sparkles, glows, and other effects to items based on rarity/value
 */

export enum ItemEffectType {
    NONE = 0,
    SPARKLE = 1,      // Small sparkles for uncommon items
    GLOW = 2,         // Soft glow for rare items  
    SHIMMER = 3,      // Shimmering effect for very rare items
    RAINBOW = 4,      // Rainbow effect for ultra-rare items
    PULSE = 5         // Pulsing effect for quest items
}

export interface ItemEffect {
    type: ItemEffectType;
    intensity: number;  // 0-100
    color?: number;     // RGB color
    speed?: number;     // Animation speed
}

export class ItemEffects {
    private static effects: Map<number, ItemEffect> = new Map();
    
    static init() {
        // Define effects for specific valuable items
        // These IDs would need to match actual item IDs in your game
        
        // Coins and gold
        this.addEffect(995, { type: ItemEffectType.SPARKLE, intensity: 30 }); // Gold coins
        
        // Runes (assuming standard rune IDs)
        for (let runeId = 554; runeId <= 566; runeId++) {
            this.addEffect(runeId, { type: ItemEffectType.GLOW, intensity: 20, color: 0x6699ff });
        }
        
        // Dragon items (example IDs - adjust to match actual game)
        this.addEffect(1187, { type: ItemEffectType.SHIMMER, intensity: 60 }); // Dragon sq shield
        this.addEffect(1149, { type: ItemEffectType.SHIMMER, intensity: 60 }); // Dragon med helm
        
        // Party hats and rares (example IDs)
        this.addEffect(1038, { type: ItemEffectType.RAINBOW, intensity: 80 }); // Red party hat
        this.addEffect(1040, { type: ItemEffectType.RAINBOW, intensity: 80 }); // Yellow party hat
        this.addEffect(1042, { type: ItemEffectType.RAINBOW, intensity: 80 }); // Blue party hat
        this.addEffect(1044, { type: ItemEffectType.RAINBOW, intensity: 80 }); // Green party hat
        this.addEffect(1046, { type: ItemEffectType.RAINBOW, intensity: 80 }); // Purple party hat
        this.addEffect(1048, { type: ItemEffectType.RAINBOW, intensity: 80 }); // White party hat
        
        // Quest items could pulse
        // Add quest item IDs here with PULSE effect
    }
    
    static addEffect(itemId: number, effect: ItemEffect) {
        this.effects.set(itemId, effect);
    }
    
    static getEffect(itemId: number): ItemEffect | undefined {
        return this.effects.get(itemId);
    }
    
    static hasEffect(itemId: number): boolean {
        return this.effects.has(itemId);
    }
    
    /**
     * Automatically determine effect based on item value
     */
    static getAutoEffect(itemId: number, value: number): ItemEffect {
        if (value >= 1000000) {
            return { type: ItemEffectType.RAINBOW, intensity: 70 };
        } else if (value >= 100000) {
            return { type: ItemEffectType.SHIMMER, intensity: 50 };
        } else if (value >= 10000) {
            return { type: ItemEffectType.GLOW, intensity: 30 };
        } else if (value >= 1000) {
            return { type: ItemEffectType.SPARKLE, intensity: 20 };
        }
        return { type: ItemEffectType.NONE, intensity: 0 };
    }
}

// Initialize on module load
ItemEffects.init();

export default ItemEffects;