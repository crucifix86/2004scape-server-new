<?php
/**
 * CORRECT Save Parser - using the real format we discovered
 * Skills are stored in little-endian format around offset 0x0590s
 */
class CorrectSaveParser {
    private $data;
    
    public function __construct($filePath) {
        $this->data = file_get_contents($filePath);
    }
    
    public function parse() {
        echo "=== CORRECT SAVE PARSER ===\n\n";
        
        // Based on our discovery, Woodcutting XP is at 0x0599
        // Let's map out where other skills might be
        
        // If Woodcutting (skill index 8) is at 0x0599, and skills are 4 bytes each...
        // Then skill 0 would be at 0x0599 - (8 * 4) = 0x0579
        
        $skillStartOffset = 0x0579;
        
        $skills = [
            'Attack', 'Defence', 'Strength', 'Hitpoints', 'Ranged', 'Prayer', 'Magic',
            'Cooking', 'Woodcutting', 'Fletching', 'Fishing', 'Firemaking', 'Crafting',
            'Smithing', 'Mining', 'Herblore', 'Agility', 'Thieving', 'Stat18', 'Stat19', 'Runecraft'
        ];
        
        $player = ['skills' => []];
        $totalLevel = 0;
        
        echo "Reading skills starting at offset 0x" . sprintf('%04X', $skillStartOffset) . ":\n\n";
        
        for ($i = 0; $i < count($skills); $i++) {
            $offset = $skillStartOffset + ($i * 4);
            
            if ($offset + 4 <= strlen($this->data)) {
                // Read as little-endian (V format)
                $xp = unpack('V', substr($this->data, $offset, 4))[1];
                $level = $this->getLevelFromXP($xp);
                
                $skillName = $skills[$i];
                $player['skills'][$skillName] = [
                    'experience' => $xp,
                    'level' => $level,
                    'current' => $level
                ];
                
                if ($i !== 18 && $i !== 19) { // Skip disabled stats
                    $totalLevel += $level;
                }
                
                printf("%-12s (0x%04X): Level %2d, XP %8d\n", $skillName, $offset, $level, $xp);
                
                // Highlight the one we know changed
                if ($skillName === 'Woodcutting' && $xp == 25) {
                    echo "  *** CONFIRMED: This matches our +25 Woodcutting XP! ***\n";
                }
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
        
        echo "\n=== CALCULATED STATS ===\n";
        echo "Combat Level: " . $player['combat_level'] . "\n";
        echo "Total Level: " . $player['total_level'] . "\n";
        
        return $player;
    }
    
    private function getLevelFromXP($xp) {
        $xpTable = [
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
        
        for ($level = 99; $level >= 1; $level--) {
            if ($xp >= $xpTable[$level]) {
                return $level;
            }
        }
        return 1;
    }
}
?>