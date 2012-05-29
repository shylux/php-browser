#!/usr/bin/php
<?php

$singleline = true;

if (!isset($argv[1])) {
	print "Please select the file.\n";
	return;
}

$file = file_get_contents($argv[1]);

$i = 2;
while (isset($argv[$i])) {
	//echo $argv[$i] . "\n";
	$content = file_get_contents($argv[$i]);
	$content = str_replace(array("\\", "'", "\t"), array("\\\\", "\'", ""), $content);
	$content = trim($content, "\n");
	if ($singleline) $content = str_replace("\n", "", $content);
	$content = "'". $content ."'";

	$search = "file_get_contents(\"".$argv[$i]."\")";
	$file = str_replace($search, $content, $file);
	$search = "file_get_contents('".$argv[$i]."')";
	$file = str_replace($search, $content, $file);

	$i++;
}
file_put_contents($argv[1], $file);
?>
