# 2004Scape Texture Upgrade Guide

## Current Texture System

2004Scape uses indexed textures stored in:
- **Pack file**: `/data/src/pack/texture.pack`
- **Compiled textures**: `/data/pack/client/textures`
- **Texture definitions**: `/tools/pack/sprite/textures.ts`

Current textures include: door, water, wall, planks, wood, roof, trees, mossy bricks, marble, fountains, etc.

## Free Texture Resources

### Best Free Sources

1. **itch.io Medieval Low-Poly Assets**
   - URL: https://itch.io/game-assets/free/tag-low-poly/tag-medieval
   - Recommended packs:
     - KayKit Medieval Hexagon Pack
     - KayKit Dungeon Pack Remastered
     - Modular Village Pack
     - Medieval Interior Asset Pack

2. **OpenGameArt.org**
   - URL: https://opengameart.org/textures/all
   - Free, open-source textures
   - Good for: stone, wood, water, terrain textures

3. **Unity Asset Store (Free)**
   - Lowpoly Environment - Nature Free - MEDIEVAL FANTASY SERIES
   - By Polytope Studio
   - Includes environmental textures

4. **Toast Enterprises Medieval Pack**
   - URL: https://toast-enterprises.itch.io/medieval-asset-pack
   - Low poly medieval themed textures

## Texture Requirements

For 2004Scape compatibility:
- **Format**: PNG or similar raster format
- **Style**: Low-poly, pixelated, or stylized (matches RuneScape 2004 aesthetic)
- **Resolution**: Generally 64x64 or 128x128 pixels
- **Color depth**: Limited palette preferred for authentic look

## How to Replace Textures

1. **Locate texture files**
   ```bash
   cd /home/crucifix/2004scape-server/data/src/sprites
   ```

2. **Prepare new textures**
   - Download free texture packs
   - Resize to appropriate dimensions (64x64 or 128x128)
   - Convert to compatible format if needed

3. **Update texture pack file**
   - Edit `/data/src/pack/texture.pack`
   - Map texture IDs to new texture names

4. **Rebuild cache**
   ```bash
   npm run build
   ```

5. **Restart server**
   ```bash
   npm run quickstart
   ```

## Recommended Workflow

1. **Download KayKit packs** (most compatible)
   - Medieval buildings textures
   - Dungeon stone textures
   - Nature/vegetation textures

2. **Process textures**
   - Use GIMP/Photoshop to resize
   - Reduce colors for retro aesthetic
   - Save as PNG

3. **Test incrementally**
   - Replace one texture type at a time
   - Test in-game after each change
   - Keep backups of original textures

## Texture Categories to Update

Priority order for visual impact:
1. **Terrain** - grass, dirt, stone, sand
2. **Buildings** - walls, roofs, doors, windows
3. **Nature** - trees, water, plants
4. **Dungeon** - mossy stones, damaged walls
5. **Special** - lava, fountains, decorative

## Legal Note

Always verify licenses:
- CC0 (Public Domain) - Best option
- CC-BY - Requires attribution
- MIT/BSD - Generally safe for use
- Check each asset pack's specific license

## Backup Original Textures

Before making changes:
```bash
cp -r /home/crucifix/2004scape-server/data/pack/client/textures /home/crucifix/2004scape-server/data/pack/client/textures.backup
```

## Community Resources

- RuneScape Classic texture packs
- OSRS HD texture projects
- RetroMMO texture communities
- Low-poly game dev forums