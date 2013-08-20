<?php

require_once('CLIInput.class.php');

echo "Tell me something: ";
$input = new CLIInput();
$text = $input->read();
echo "\n\nRESULT: ".$text."\n";
?>