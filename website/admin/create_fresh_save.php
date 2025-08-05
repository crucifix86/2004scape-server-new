<?php
// Create a completely fresh starter save file

echo "=== CREATING FRESH STARTER SAVE ===\n\n";

// Start with the current corrupted file as a template
$template = file_get_contents('/home/crucifix/Server/data/players/main/crucifix.sav');

// Create new data array
$save = str_repeat("\x00", strlen($template)); // Start with zeros

// Copy the structure parts we know are correct
// Header and version
$save[0] = $template[0]; // 0x20
$save[1] = $template[1]; // 0x04  
$save[2] = $template[2]; // version
$save[3] = $template[3]; // version

// Position (copy from template)
for ($i = 4; $i <= 8; $i++) {
    $save[$i] = $template[$i];
}

// Appearance and colors (copy from template)
for ($i = 9; $i <= 0x15; $i++) {
    $save[$i] = $template[$i];
}

// Run energy (10000 = 100%)
$save[0x16] = chr(0x27);
$save[0x17] = chr(0x10);

// Playtime (copy from template)
for ($i = 0x18; $i <= 0x1B; $i++) {
    $save[$i] = $template[$i];
}

// Skills XP - all 0 except HP
for ($i = 0; $i < 21; $i++) {
    $offset = 0x1C + ($i * 4);
    if ($i == 3) { // Hitpoints
        // 1154 = 0x00000482
        $save[$offset] = chr(0x00);
        $save[$offset + 1] = chr(0x00);
        $save[$offset + 2] = chr(0x04);
        $save[$offset + 3] = chr(0x82);
    } else {
        // 0 XP
        $save[$offset] = chr(0x00);
        $save[$offset + 1] = chr(0x00);
        $save[$offset + 2] = chr(0x00);
        $save[$offset + 3] = chr(0x00);
    }
}

// Current levels - all 1 except HP
for ($i = 0; $i < 21; $i++) {
    $offset = 0x70 + $i;
    if ($i == 3) { // Hitpoints
        $save[$offset] = chr(10); // Level 10
    } else {
        $save[$offset] = chr(1); // Level 1
    }
}

// Combat level = 3 at offset 0x0C
$save[0x0C] = chr(0x00);
$save[0x0D] = chr(0x03);

// Copy the rest of the file structure (inventory, bank, etc)
for ($i = 0x85; $i < strlen($template); $i++) {
    $save[$i] = $template[$i];
}

// Save it
$outputFile = '/tmp/crucifix_fresh_starter.sav';
file_put_contents($outputFile, $save);
chmod($outputFile, 0666);

echo "Fresh starter save created at: $outputFile\n";
echo "\nThis save has:\n";
echo "- All skills at level 1 (0 XP)\n";
echo "- Hitpoints at level 10 (1,154 XP)\n";
echo "- Combat level 3\n";
echo "- Your original position and appearance\n";
echo "- Your original inventory/bank data\n";
echo "\nTo use it:\n";
echo "sudo cp $outputFile /home/crucifix/Server/data/players/main/crucifix.sav\n";

// Verify the key values
echo "\n=== VERIFICATION ===\n";
$hpXp = unpack('N', substr($save, 0x28, 4))[1];
echo "HP XP: $hpXp (should be 1154)\n";
$combat = unpack('n', substr($save, 0x0C, 2))[1];
echo "Combat Level: $combat (should be 3)\n";
?>