<?php
/**
 * REAL Player Save Parser - actually reads the save file data
 * Instead of using hardcoded values like the old parser
 */
class RealPlayerSaveParser {
    private $data;
    private $position = 0;
    
    // XP table for level calculation
    private static $XP_TABLE = [
        1 => 0, 2 => 83, 3 => 174, 4 => 276, 5 => 388, 6 => 512, 7 => 650, 8 => 801,
        9 => 969, 10 => 1154, 11 => 1358, 12 => 1584, 13 => 1833, 14 => 2107,
        15 => 2411, 16 => 2746, 17 => 3115, 18 => 3523, 19 => 3973, 20 => 4470,
        21 => 5018, 22 => 5624, 23 => 6291, 24 => 7028, 25 => 7842, 26 => 8740,
        27 => 9730, 28 => 10824, 29 => 12031, 30 => 13363, 31 => 14833, 32 => 16456,
        33 => 18247, 34 => 20224, 35 => 22406, 36 => 24815, 37 => 27473, 38 => 30408,
        39 => 33648, 40 => 37224, 41 => 41171, 42 => 45529, 43 => 50339, 44 => 55649,
        45 => 61512, 46 => 67983, 47 => 75127, 48 => 83014, 49 => 91721, 50 => 101333,
        51 => 111945, 52 => 123660, 53 => 136594, 54 => 150872, 55 => 166636,
        56 => 184040, 57 => 203254, 58 => 224466, 59 => 247886, 60 => 273742,
        61 => 302288, 62 => 333804, 63 => 368599, 64 => 407015, 65 => 449428,
        66 => 496254, 67 => 547953, 68 => 605032, 69 => 668051, 70 => 737627,
        71 => 814445, 72 => 899257, 73 => 992895, 74 => 1096278, 75 => 1210421,
        76 => 1336443, 77 => 1475581, 78 => 1629200, 79 => 1798808, 80 => 1986068,
        81 => 2192818, 82 => 2421087, 83 => 2673114, 84 => 2951373, 85 => 3258594,
        86 => 3597792, 87 => 3972294, 88 => 4385776, 89 => 4842295, 90 => 5346332,
        91 => 5902831, 92 => 6517253, 93 => 7195629, 94 => 7944614, 95 => 8771558,
        96 => 9684577, 97 => 10692629, 98 => 11805606, 99 => 13034431
    ];
    
    private static $SKILL_NAMES = [
        'Attack', 'Defence', 'Strength', 'Hitpoints', 'Ranged', 'Prayer', 'Magic',
        'Cooking', 'Woodcutting', 'Fletching', 'Fishing', 'Firemaking', 'Crafting',
        'Smithing', 'Mining', 'Herblore', 'Agility', 'Thieving', 'Stat18', 'Stat19', 'Runecraft'
    ];
    
    public function __construct($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Save file not found: $filePath");
        }
        
        $this->data = file_get_contents($filePath);
        if ($this->data === false) {
            throw new Exception("Failed to read save file");
        }
    }
    
    public function parse() {
        $this->position = 0;
        $player = [];
        
        // Parse header
        $signature = $this->g2();
        if ($signature !== 0x2004) {
            throw new Exception("Invalid save file signature: 0x" . sprintf('%04X', $signature));
        }
        
        $version = $this->g2();
        $player['version'] = $version;
        
        // Parse position
        $x = $this->g2();
        $z = $this->g2();
        $level = $this->g1();
        $player['position'] = ['x' => $x, 'z' => $z, 'level' => $level];
        
        // Skip appearance and colors (12 bytes)
        $this->position += 12;
        
        // Parse gender, run energy, playtime
        $player['gender'] = $this->g1() === 0 ? 'Male' : 'Female';
        $player['run_energy'] = $this->g2();
        $player['playtime'] = $this->g4();
        
        // Now we're at offset 0x1C - start of skills XP
        echo "Reading skills XP starting at offset: 0x" . sprintf('%02X', $this->position) . "\n";
        
        $player['skills'] = [];
        $totalLevel = 0;
        
        // Read XP values for all 21 skills
        for ($i = 0; $i < 21; $i++) {
            $skillName = self::$SKILL_NAMES[$i];
            $xp = $this->g4(); // 4-byte big-endian
            $level = $this->getLevelFromXP($xp);
            
            echo "Skill $i ($skillName): XP=$xp, Level=$level (offset 0x" . sprintf('%02X', $this->position - 4) . ")\n";
            
            $player['skills'][$skillName] = [
                'experience' => $xp,
                'level' => $level,
                'current' => $level
            ];
            
            if ($i !== 18 && $i !== 19) { // Skip Stat18/Stat19 for total
                $totalLevel += $level;
            }
        }
        
        // Read current levels (for temporary boosts/drains)
        echo "\nReading current levels starting at offset: 0x" . sprintf('%02X', $this->position) . "\n";
        for ($i = 0; $i < 21; $i++) {
            $skillName = self::$SKILL_NAMES[$i];
            $currentLevel = $this->g1();
            
            if (isset($player['skills'][$skillName])) {
                $player['skills'][$skillName]['current'] = $currentLevel;
                echo "Skill $i ($skillName): Current level = $currentLevel\n";
            }
        }
        
        $player['total_level'] = $totalLevel;
        
        // Calculate combat level
        $attack = $player['skills']['Attack']['level'];
        $defence = $player['skills']['Defence']['level'];
        $strength = $player['skills']['Strength']['level'];
        $hitpoints = $player['skills']['Hitpoints']['level'];
        $prayer = $player['skills']['Prayer']['level'];
        $ranged = $player['skills']['Ranged']['level'];
        $magic = $player['skills']['Magic']['level'];
        
        $base = 0.25 * ($defence + $hitpoints + floor($prayer / 2));
        $melee = 0.325 * ($attack + $strength);
        $range = 0.325 * (floor($ranged * 1.5));
        $mage = 0.325 * (floor($magic * 1.5));
        
        $player['combat_level'] = floor($base + max($melee, $range, $mage));
        
        echo "\nCalculated combat level: " . $player['combat_level'] . "\n";
        echo "Total level: " . $player['total_level'] . "\n";
        
        return $player;
    }
    
    private function g1() {
        if ($this->position >= strlen($this->data)) {
            throw new Exception("Unexpected end of file");
        }
        return ord($this->data[$this->position++]);
    }
    
    private function g2() {
        if ($this->position + 1 >= strlen($this->data)) {
            throw new Exception("Unexpected end of file");
        }
        $value = unpack('n', substr($this->data, $this->position, 2))[1];
        $this->position += 2;
        return $value;
    }
    
    private function g4() {
        if ($this->position + 3 >= strlen($this->data)) {
            throw new Exception("Unexpected end of file");
        }
        $value = unpack('N', substr($this->data, $this->position, 4))[1];
        $this->position += 4;
        return $value;
    }
    
    private function getLevelFromXP($xp) {
        for ($level = 99; $level >= 1; $level--) {
            if ($xp >= self::$XP_TABLE[$level]) {
                return $level;
            }
        }
        return 1;
    }
}
?>