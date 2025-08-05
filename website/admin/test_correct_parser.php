<?php
require_once 'lib/CorrectSaveParser.php';

$parser = new CorrectSaveParser('/home/crucifix/Server/data/players/main/crucifix.sav');
$data = $parser->parse();
?>