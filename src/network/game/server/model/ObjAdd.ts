import ItemEffects from '#/engine/entity/ItemEffects.js';
import ZoneMessage from '#/network/game/server/ZoneMessage.js';

export default class ObjAdd extends ZoneMessage {
    readonly effect?: number;
    
    constructor(
        readonly coord: number,
        readonly obj: number,
        readonly count: number
    ) {
        super(coord);
        
        // Check if this item has a visual effect
        const itemEffect = ItemEffects.getEffect(obj);
        if (itemEffect) {
            this.effect = itemEffect.type;
        }
    }
}
