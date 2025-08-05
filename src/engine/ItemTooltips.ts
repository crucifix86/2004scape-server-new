/**
 * Enhanced Item Tooltip System
 * Shows detailed stats and information when examining items
 */

import ObjType from '#/cache/config/ObjType.js';

export interface ItemStats {
    // Combat bonuses
    attackStab?: number;
    attackSlash?: number;
    attackCrush?: number;
    attackMagic?: number;
    attackRange?: number;
    
    // Defense bonuses
    defenseStab?: number;
    defenseSlash?: number;
    defenseCrush?: number;
    defenseMagic?: number;
    defenseRange?: number;
    
    // Other bonuses
    strengthBonus?: number;
    prayerBonus?: number;
    magicDamage?: number;
    rangedStrength?: number;
    
    // Requirements
    attackReq?: number;
    strengthReq?: number;
    defenseReq?: number;
    rangedReq?: number;
    magicReq?: number;
    prayerReq?: number;
    
    // Item properties
    weight?: number;
    value?: number;
    alchValue?: number;
    stackable?: boolean;
    tradeable?: boolean;
    members?: boolean;
}

export class ItemTooltips {
    private static itemStats: Map<number, ItemStats> = new Map();
    
    static init() {
        // Initialize with some example item stats
        // In a real implementation, these would be loaded from data files
        
        // Bronze sword
        this.setStats(1277, {
            attackStab: 4,
            attackSlash: 4,
            attackCrush: -2,
            strengthBonus: 3,
            weight: 2.2,
            value: 15,
            alchValue: 9
        });
        
        // Iron sword
        this.setStats(1279, {
            attackStab: 7,
            attackSlash: 6,
            attackCrush: -2,
            strengthBonus: 5,
            attackReq: 1,
            weight: 2.2,
            value: 56,
            alchValue: 33
        });
        
        // Rune scimitar
        this.setStats(1333, {
            attackStab: 7,
            attackSlash: 45,
            attackCrush: -2,
            strengthBonus: 44,
            attackReq: 40,
            weight: 1.8,
            value: 32000,
            alchValue: 19200
        });
        
        // Dragon longsword
        this.setStats(1305, {
            attackStab: 58,
            attackSlash: 69,
            attackCrush: -2,
            strengthBonus: 71,
            attackReq: 60,
            weight: 2.0,
            value: 100000,
            alchValue: 60000,
            members: true
        });
        
        // Blue wizard hat
        this.setStats(579, {
            defenseStab: 2,
            defenseSlash: 3,
            defenseCrush: 1,
            defenseMagic: 2,
            weight: 0.4,
            value: 2,
            alchValue: 1
        });
        
        // Mystic robe top
        this.setStats(4091, {
            defenseStab: 20,
            defenseSlash: 18,
            defenseCrush: 22,
            defenseMagic: 20,
            attackMagic: 20,
            magicReq: 40,
            defenseReq: 20,
            weight: 0.9,
            value: 120000,
            alchValue: 72000,
            members: true
        });
    }
    
    static setStats(itemId: number, stats: ItemStats) {
        this.itemStats.set(itemId, stats);
    }
    
    static getStats(itemId: number): ItemStats | undefined {
        return this.itemStats.get(itemId);
    }
    
    /**
     * Generate a detailed tooltip for an item
     */
    static generateTooltip(itemId: number): string[] {
        const objType = ObjType.get(itemId);
        if (!objType) return ['Unknown item'];
        
        const lines: string[] = [];
        const stats = this.getStats(itemId);
        
        // Item name and basic info
        lines.push(`§e${objType.name}§r`); // Yellow name
        
        if (objType.desc) {
            lines.push(`§7${objType.desc}§r`); // Gray description
        }
        
        if (!stats) {
            // Basic info only
            if (objType.cost > 0) {
                lines.push(`§fValue: §6${this.formatNumber(objType.cost)} gp§r`);
            }
            return lines;
        }
        
        // Combat stats
        if (this.hasCombatStats(stats)) {
            lines.push('§b--- Combat Stats ---§r');
            
            // Attack bonuses
            if (stats.attackStab) lines.push(`§fStab: §a+${stats.attackStab}§r`);
            if (stats.attackSlash) lines.push(`§fSlash: §a+${stats.attackSlash}§r`);
            if (stats.attackCrush) lines.push(`§fCrush: §a+${stats.attackCrush}§r`);
            if (stats.attackMagic) lines.push(`§fMagic: §a+${stats.attackMagic}§r`);
            if (stats.attackRange) lines.push(`§fRange: §a+${stats.attackRange}§r`);
            
            // Defense bonuses
            if (this.hasDefenseStats(stats)) {
                lines.push('§b--- Defense ---§r');
                if (stats.defenseStab) lines.push(`§fStab: §a+${stats.defenseStab}§r`);
                if (stats.defenseSlash) lines.push(`§fSlash: §a+${stats.defenseSlash}§r`);
                if (stats.defenseCrush) lines.push(`§fCrush: §a+${stats.defenseCrush}§r`);
                if (stats.defenseMagic) lines.push(`§fMagic: §a+${stats.defenseMagic}§r`);
                if (stats.defenseRange) lines.push(`§fRange: §a+${stats.defenseRange}§r`);
            }
            
            // Other bonuses
            if (stats.strengthBonus) lines.push(`§fStrength: §a+${stats.strengthBonus}§r`);
            if (stats.prayerBonus) lines.push(`§fPrayer: §a+${stats.prayerBonus}§r`);
            if (stats.magicDamage) lines.push(`§fMagic damage: §a+${stats.magicDamage}%§r`);
            if (stats.rangedStrength) lines.push(`§fRanged strength: §a+${stats.rangedStrength}§r`);
        }
        
        // Requirements
        if (this.hasRequirements(stats)) {
            lines.push('§c--- Requirements ---§r');
            if (stats.attackReq) lines.push(`§fAttack: §e${stats.attackReq}§r`);
            if (stats.strengthReq) lines.push(`§fStrength: §e${stats.strengthReq}§r`);
            if (stats.defenseReq) lines.push(`§fDefense: §e${stats.defenseReq}§r`);
            if (stats.rangedReq) lines.push(`§fRanged: §e${stats.rangedReq}§r`);
            if (stats.magicReq) lines.push(`§fMagic: §e${stats.magicReq}§r`);
            if (stats.prayerReq) lines.push(`§fPrayer: §e${stats.prayerReq}§r`);
        }
        
        // Properties
        lines.push('§9--- Properties ---§r');
        if (stats.value !== undefined) {
            lines.push(`§fValue: §6${this.formatNumber(stats.value)} gp§r`);
        }
        if (stats.alchValue !== undefined) {
            lines.push(`§fHigh alch: §6${this.formatNumber(stats.alchValue)} gp§r`);
        }
        if (stats.weight !== undefined) {
            lines.push(`§fWeight: §7${stats.weight} kg§r`);
        }
        if (stats.stackable) {
            lines.push(`§aStackable§r`);
        }
        if (stats.members) {
            lines.push(`§dMembers only§r`);
        }
        
        return lines;
    }
    
    /**
     * Generate a compact tooltip (for hover)
     */
    static generateCompactTooltip(itemId: number): string {
        const objType = ObjType.get(itemId);
        if (!objType) return 'Unknown item';
        
        const stats = this.getStats(itemId);
        if (!stats) return objType.name;
        
        const parts = [objType.name];
        
        // Add key stats
        if (stats.attackSlash) parts.push(`Att:${stats.attackSlash}`);
        if (stats.strengthBonus) parts.push(`Str:${stats.strengthBonus}`);
        if (stats.defenseSlash) parts.push(`Def:${stats.defenseSlash}`);
        if (stats.value) parts.push(`${this.formatNumber(stats.value)}gp`);
        
        return parts.join(' | ');
    }
    
    private static hasCombatStats(stats: ItemStats): boolean {
        return !!(stats.attackStab || stats.attackSlash || stats.attackCrush || 
                 stats.attackMagic || stats.attackRange || stats.strengthBonus);
    }
    
    private static hasDefenseStats(stats: ItemStats): boolean {
        return !!(stats.defenseStab || stats.defenseSlash || stats.defenseCrush || 
                 stats.defenseMagic || stats.defenseRange);
    }
    
    private static hasRequirements(stats: ItemStats): boolean {
        return !!(stats.attackReq || stats.strengthReq || stats.defenseReq || 
                 stats.rangedReq || stats.magicReq || stats.prayerReq);
    }
    
    private static formatNumber(num: number): string {
        if (num >= 1000000) {
            return `${(num / 1000000).toFixed(1)}M`;
        } else if (num >= 1000) {
            return `${(num / 1000).toFixed(1)}K`;
        }
        return num.toString();
    }
}

// Initialize on module load
ItemTooltips.init();

export default ItemTooltips;