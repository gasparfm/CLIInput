<?php

require_once('CLIInput.class.php');

function testString($string, $state, $options)
{
  return array('string' => 'Crash Test Dummy',
	       'pos' =>2);
}

function testString2($string, $state, $options)
{
  return array('string' => strtoupper($string));
}

function emptyExit($string, $state, $options)
{
  return array('string' => '',
	       'end' => true);
}

echo "Enter your name: ";
$input = new CLIInput();
$text = $input->read(20, 
		     array('up' => 'testString',
			   'down' => 'testString2',
			   'escape' => 'emptyExit'));
echo "\n\nRESULT: ".$text."\n";
?>