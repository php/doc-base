#!/usr/bin/php -q
<?php
/*
There are no restrictions on this file.
Author: Jakub Vr�na <jakub@vrana.cz>
*/
if ($_SERVER["argc"] < 3) {
	exit("Purpose: Syntax highlight PHP examples in DSSSL generated HTML manual.\n"
		.'Usage: html_syntax.php [ "html" | "php" ] [ filename.ext | dir | wildcard ] ...' ."\n"
		.'"html" - highlight_string() is applied, "php" - highlight_php() is added' ."\n"
	);
}
set_time_limit(5*60); // can run long, but not more than 5 minutes

function callback_html_number_entities_decode($matches) {
	return chr($matches[1]);
}

function callback_highlight_php($matches) {
	$with_tags = preg_replace_callback("!&#([0-9]+);!", "callback_html_number_entities_decode", $matches[1]);
	if ($GLOBALS["TYPE"] == "php") {
		return "\n<?php\nhighlight_php('". addcslashes($with_tags, "'\\") ."');\n?>\n";
	} else { // "html"
		return highlight_string($with_tags, true);
	}
}

$files = $_SERVER["argv"];
array_shift($files); // $argv[0] - script filename
$TYPE = array_shift($files); // "html" or "php"
while (($file = array_shift($files)) !== null) {
	if (is_file($file)) {
		$process = array($file);
	} elseif (is_dir($file)) {
		$lastchar = substr($file, -1);
		$process = glob($file . ($lastchar == "/" || $lastchar == "\\" ? "*" : "/*"));
	} else { // should be wildcard
		$process = glob($file);
	}
	foreach ($process as $filename) {
		if (!is_file($filename)) { // do not recurse
			continue;
		}
		//~ echo "$filename\n";
		$original = file_get_contents($filename);
		$highlighted = preg_replace_callback("!<PRE\r?\nCLASS=\"php\"\r?\n>(.*)</PRE\r?\n>!sU", "callback_highlight_php", $original);
		if ($original != $highlighted) {
			// file_put_contents is only in PHP >= 5
			$fp = fopen($filename, "wb");
			fwrite($fp, $highlighted);
			fclose($fp);
		}
	}
}
?>
