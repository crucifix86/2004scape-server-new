/**
 * Bank Search and Filter System
 * Allows players to search and filter their bank items
 */

import ObjType from '#/cache/config/ObjType.js';
import Inventory from '#/engine/Inventory.js';

export interface BankSearchOptions {
    searchTerm?: string;
    minValue?: number;
    maxValue?: number;
    category?: string;
    stackableOnly?: boolean;
    notedOnly?: boolean;
}

export class BankSearch {
    private static searchHistory: Map<string, string[]> = new Map();
    private static MAX_HISTORY = 10;
    
    /**
     * Search bank inventory for items matching criteria
     */
    static searchBank(bank: Inventory, options: BankSearchOptions): number[] {
        const matchingSlots: number[] = [];
        const searchTerm = options.searchTerm?.toLowerCase();
        
        for (let slot = 0; slot < bank.capacity; slot++) {
            const item = bank.get(slot);
            if (!item) continue;
            
            const objType = ObjType.get(item.id);
            if (!objType) continue;
            
            // Text search
            if (searchTerm && !objType.name.toLowerCase().includes(searchTerm)) {
                continue;
            }
            
            // Value filter
            if (options.minValue !== undefined && objType.cost < options.minValue) {
                continue;
            }
            if (options.maxValue !== undefined && objType.cost > options.maxValue) {
                continue;
            }
            
            // Stackable filter
            if (options.stackableOnly && !objType.stackable) {
                continue;
            }
            
            // Noted filter
            if (options.notedOnly && !objType.certtemplate) {
                continue;
            }
            
            // Category filter (weapons, armor, food, etc)
            if (options.category && !this.matchesCategory(objType, options.category)) {
                continue;
            }
            
            matchingSlots.push(slot);
        }
        
        return matchingSlots;
    }
    
    /**
     * Quick search suggestions based on partial input
     */
    static getSuggestions(bank: Inventory, partial: string): string[] {
        const suggestions = new Set<string>();
        const searchTerm = partial.toLowerCase();
        
        for (let slot = 0; slot < bank.capacity; slot++) {
            const item = bank.get(slot);
            if (!item) continue;
            
            const objType = ObjType.get(item.id);
            if (!objType) continue;
            
            if (objType.name.toLowerCase().includes(searchTerm)) {
                suggestions.add(objType.name);
                if (suggestions.size >= 5) break; // Limit suggestions
            }
        }
        
        return Array.from(suggestions);
    }
    
    /**
     * Add search to history for quick access
     */
    static addToHistory(username: string, searchTerm: string) {
        if (!this.searchHistory.has(username)) {
            this.searchHistory.set(username, []);
        }
        
        const history = this.searchHistory.get(username)!;
        
        // Remove if already exists
        const index = history.indexOf(searchTerm);
        if (index > -1) {
            history.splice(index, 1);
        }
        
        // Add to front
        history.unshift(searchTerm);
        
        // Keep only MAX_HISTORY items
        if (history.length > this.MAX_HISTORY) {
            history.pop();
        }
    }
    
    /**
     * Get search history for a player
     */
    static getHistory(username: string): string[] {
        return this.searchHistory.get(username) || [];
    }
    
    /**
     * Check if item matches a category
     */
    private static matchesCategory(objType: ObjType, category: string): boolean {
        const name = objType.name.toLowerCase();
        
        switch (category.toLowerCase()) {
            case 'weapon':
                return name.includes('sword') || name.includes('axe') || name.includes('bow') ||
                       name.includes('staff') || name.includes('dagger') || name.includes('mace');
            
            case 'armor':
            case 'armour':
                return name.includes('helm') || name.includes('body') || name.includes('legs') ||
                       name.includes('shield') || name.includes('boots') || name.includes('gloves') ||
                       name.includes('platebody') || name.includes('chainbody');
            
            case 'food':
                return name.includes('cake') || name.includes('bread') || name.includes('fish') ||
                       name.includes('meat') || name.includes('pie') || name.includes('lobster') ||
                       name.includes('shark') || name.includes('salmon');
            
            case 'rune':
                return name.includes('rune') && !name.includes('runite');
            
            case 'potion':
                return name.includes('potion') || name.includes('dose');
            
            case 'herb':
                return name.includes('herb') || name.includes('grimy') || name.includes('clean');
            
            case 'seed':
                return name.includes('seed');
            
            case 'ore':
                return name.includes('ore');
            
            case 'bar':
                return name.includes('bar') && !name.includes('barbarian');
            
            case 'gem':
                return name.includes('sapphire') || name.includes('emerald') || name.includes('ruby') ||
                       name.includes('diamond') || name.includes('dragonstone') || name.includes('opal') ||
                       name.includes('jade') || name.includes('topaz');
            
            default:
                return false;
        }
    }
    
    /**
     * Get total value of all items in bank
     */
    static getBankValue(bank: Inventory): number {
        let totalValue = 0;
        
        for (let slot = 0; slot < bank.capacity; slot++) {
            const item = bank.get(slot);
            if (!item) continue;
            
            const objType = ObjType.get(item.id);
            if (!objType) continue;
            
            totalValue += objType.cost * item.count;
        }
        
        return totalValue;
    }
    
    /**
     * Get bank statistics
     */
    static getBankStats(bank: Inventory): {
        totalItems: number;
        totalStacks: number;
        totalValue: number;
        mostValuableItem: { name: string; value: number } | null;
    } {
        let totalItems = 0;
        let totalStacks = 0;
        let totalValue = 0;
        let mostValuableItem: { name: string; value: number } | null = null;
        
        for (let slot = 0; slot < bank.capacity; slot++) {
            const item = bank.get(slot);
            if (!item) continue;
            
            const objType = ObjType.get(item.id);
            if (!objType) continue;
            
            totalStacks++;
            totalItems += item.count;
            const itemValue = objType.cost * item.count;
            totalValue += itemValue;
            
            if (!mostValuableItem || itemValue > mostValuableItem.value) {
                mostValuableItem = {
                    name: objType.name,
                    value: itemValue
                };
            }
        }
        
        return {
            totalItems,
            totalStacks,
            totalValue,
            mostValuableItem
        };
    }
}

export default BankSearch;