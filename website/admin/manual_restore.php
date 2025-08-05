<?php
// Manual restoration tool

echo "MANUAL RESTORATION REQUIRED!\n";
echo "============================\n\n";

echo "Your save file has been corrupted. To restore it, you need to run this command in your terminal:\n\n";

echo "sudo cp /home/crucifix/Server/data/players/main/crucifix.sav.backup_1754026820 /home/crucifix/Server/data/players/main/crucifix.sav && sudo chmod 666 /home/crucifix/Server/data/players/main/crucifix.sav\n\n";

echo "This will restore your character to its previous state with:\n";
echo "- Combat Level: 3\n";
echo "- Total Level: 28\n";
echo "- All skills at level 1 (HP at level 10)\n";
echo "- Your original inventory intact\n\n";

echo "The corruption happened because the save writer had a bug that didn't properly handle the data format.\n";
echo "I'll fix this before we try editing again.\n";
?>