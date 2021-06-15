<?php
/*
Introduction:
	- This script checks for optional parameters that do not utilize the <initializer> tag.
	- Pass in a path and it'll check it. The path might include all of phpdoc, or a simple extension
TODO:
	- Determine what initializer values should be as some cases aren't clear
*/

$opts = getopt('p:oh');

if (isset($opts['h'])) {
	usage();
}
if (empty($opts['p'])) {
	echo "\nERROR:\n - A path is required\n";
	usage();
}
if (!is_dir($opts['p'])) {
	echo "\nERROR:\n - Please pass in a real directory, unlike this mysterious '$opts[p]'\n";
	usage();
}

$empty = [];
$count_total = 0;
$count_empty = 0;

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($opts['p'])) as $file) {

	$filepath = $file->getPathname();
	$filename = $file->getBasename();

	if (!$file->isFile() || pathinfo($filepath, PATHINFO_EXTENSION) !== 'xml') {
		continue;
	}
	
	$contents = file_get_contents($filepath);

	$matches = [];
	preg_match_all('@<methodparam choice="opt"><type>(.*)</type><parameter>(.*)</parameter>(.*)</methodparam>@', $contents, $matches);

	// Check if any optional parameters exist
	if (empty($matches)) {
		continue;
	}

	// Log optional parameters without default values
	// We use the <initializer> DocBook tag for this task.
	foreach ($matches[3] as $index => $match) {
		$count_total++;
		if (empty($match) || (!str_contains($match, '<initializer>'))) {
			$count_empty++;
			// index 2 corresponds to the param, index 1 to the type
			$empty[$filepath][$matches[2][$index]] = $matches[1][$index];
		}
	}
}

// This output could be more useful
if (array_key_exists('o', $opts)) {
    foreach ($empty as $file => $issues) {
        echo $file, ' has ', count($issues), ' optional parameters without initializers:', "\n";
        foreach ($issues as $param => $type) {
            echo '- Param "', $param, '" of type "', $type, "\"\n";
        }
    }
}

print "Found $count_total optional parameters, and $count_empty are empty.\n";

function usage() {
	echo "\nUSAGE:\n";
	echo "$ php {$_SERVER['SCRIPT_FILENAME']} -p /path/to/phpdoc/docs/to/check\n";
	echo "  Optional: Add -o to output the results.\n";
	exit;
}
