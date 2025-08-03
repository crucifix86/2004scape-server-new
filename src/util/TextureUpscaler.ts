import fs from 'fs';
import path from 'path';

import { Jimp } from 'jimp';

export interface UpscaleConfig {
    enabled: boolean;
    scale: number;
    algorithm: 'nearest' | 'bilinear' | 'bicubic' | 'hermite' | 'bezier';
    sharpen: boolean;
    enhance: boolean;
    cache: boolean;
}

const DEFAULT_CONFIG: UpscaleConfig = {
    enabled: true,
    scale: 2,
    algorithm: 'bicubic',
    sharpen: true,
    enhance: true,
    cache: true
};

export class TextureUpscaler {
    private config: UpscaleConfig;
    private cacheDir: string;
    private textureConfigs: Record<string, Record<string, boolean>> = {};
    
    constructor(config: Partial<UpscaleConfig> = {}) {
        // Try to load config from file
        const configPath = 'data/config/upscaler.json';
        if (fs.existsSync(configPath)) {
            const fileConfig = JSON.parse(fs.readFileSync(configPath, 'utf-8'));
            this.config = { ...DEFAULT_CONFIG, ...fileConfig, ...config };
            this.textureConfigs = fileConfig.textures || {};
        } else {
            this.config = { ...DEFAULT_CONFIG, ...config };
        }
        
        this.cacheDir = 'data/cache/upscaled_textures';
        
        if (this.config.cache && !fs.existsSync(this.cacheDir)) {
            fs.mkdirSync(this.cacheDir, { recursive: true });
        }
    }
    
    async upscaleTexture(imagePath: string): Promise<Jimp> {
        if (!this.config.enabled) {
            return await Jimp.read(imagePath);
        }
        
        // Get texture-specific config
        const textureName = path.basename(imagePath, '.png');
        const textureConfig = this.textureConfigs[textureName] || {};
        
        const cachePath = this.getCachePath(imagePath);
        
        // Check cache first
        if (this.config.cache && fs.existsSync(cachePath)) {
            const cachedStats = fs.statSync(cachePath);
            const originalStats = fs.statSync(imagePath);
            
            // Use cache if it's newer than the original
            if (cachedStats.mtime > originalStats.mtime) {
                console.log(`Using cached upscaled texture: ${textureName}`);
                return await Jimp.read(cachePath);
            }
        }
        
        if (!fs.existsSync(imagePath)) {
            console.error(`Texture file not found: ${imagePath}`);
            throw new Error(`Texture file not found: ${imagePath}`);
        }
        console.log(`[UPSCALER] Processing texture: ${textureName}`);
        
        // Load and process texture
        const img = await Jimp.read(imagePath);
        const processed = await this.processTexture(img, textureConfig);
        
        // Save to cache
        if (this.config.cache) {
            await processed.write(cachePath);
        }
        
        return processed;
    }
    
    private async processTexture(img: Jimp, textureConfig: Record<string, boolean> = {}): Promise<Jimp> {
        let result = img.clone();
        
        // Merge texture-specific config with global config
        const enhance = textureConfig.enhance !== undefined ? textureConfig.enhance : this.config.enhance;
        const sharpen = textureConfig.sharpen !== undefined ? textureConfig.sharpen : this.config.sharpen;
        const waterEffect = textureConfig.waterEffect || false;
        const grassEffect = textureConfig.grassEffect || false;
        
        // Upscale to higher resolution for processing
        const tempScale = this.config.scale;
        const originalWidth = result.bitmap.width;
        const originalHeight = result.bitmap.height;
        
        // Upscale using selected algorithm
        switch (this.config.algorithm) {
            case 'nearest':
                result = result.resize({ w: originalWidth * tempScale, h: originalHeight * tempScale, mode: 'nearestNeighbor' });
                break;
            case 'bilinear':
                result = result.resize({ w: originalWidth * tempScale, h: originalHeight * tempScale, mode: 'bilinearInterpolation' });
                break;
            case 'bicubic':
                result = result.resize({ w: originalWidth * tempScale, h: originalHeight * tempScale, mode: 'bicubicInterpolation' });
                break;
            case 'hermite':
                result = result.resize({ w: originalWidth * tempScale, h: originalHeight * tempScale, mode: 'hermiteInterpolation' });
                break;
            case 'bezier':
                result = result.resize({ w: originalWidth * tempScale, h: originalHeight * tempScale, mode: 'bezierInterpolation' });
                break;
        }
        
        // Apply special water effect
        if (waterEffect) {
            // Water-specific enhancements
            result = result.color([
                { apply: 'hue', params: [-5] },  // Slightly shift to deeper blue
                { apply: 'saturate', params: [70] },  // Very vibrant water
                { apply: 'brighten', params: [10] }
            ]);
            result = result.blur(0.5);  // Slight blur for water smoothness
            result = result.contrast(0.2);
            
            // Add shimmer effect
            result = result.color([
                { apply: 'mix', params: ['#0066cc', 10] }  // Mix in ocean blue
            ]);
        }
        // Apply special grass effect
        else if (grassEffect) {
            // Grass/plant-specific enhancements
            result = result.color([
                { apply: 'hue', params: [-10] },  // Shift to more natural green
                { apply: 'saturate', params: [80] },  // Very vibrant greens
                { apply: 'brighten', params: [5] }
            ]);
            result = result.contrast(0.4);
            
            // Add lushness
            result = result.color([
                { apply: 'mix', params: ['#228B22', 15] }  // Mix in forest green
            ]);
            
            // Extra sharpness for grass detail
            result = result.contrast(0.3);
        }
        // Apply standard EXTREME enhancement filters for testing
        else if (enhance) {
            // Massively increase contrast
            result = result.contrast(0.5);
            
            // Dramatically boost colors and vibrancy
            result = result.color([
                { apply: 'saturate', params: [50] },
                { apply: 'brighten', params: [15] }
            ]);
            
            // Add extra pop to colors
            result = result.contrast(0.3);
        }
        
        // Apply EXTREME sharpening for testing
        if (sharpen) {
            // Aggressive sharpening using multiple contrast passes
            result = result.contrast(0.4);
            result = result.contrast(0.3);
            
            // Extra edge enhancement
            result = result.color([
                { apply: 'xor', params: ['#060606'] }
            ]);
        }
        
        // Scale back down to original size
        // This gives us the enhanced quality at original resolution
        result = result.resize({ w: originalWidth, h: originalHeight, mode: 'bicubicInterpolation' });
        
        return result;
    }
    
    private getCachePath(originalPath: string): string {
        const basename = path.basename(originalPath);
        return path.join(this.cacheDir, `upscaled_${basename}`);
    }
    
    clearCache(): void {
        if (fs.existsSync(this.cacheDir)) {
            const files = fs.readdirSync(this.cacheDir);
            files.forEach(file => {
                fs.unlinkSync(path.join(this.cacheDir, file));
            });
        }
    }
}

// Global instance
export const textureUpscaler = new TextureUpscaler();