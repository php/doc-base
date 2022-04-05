<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 2009-2011 The PHP Group                                     |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt.                                 |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Philip Olson   <philip@php.net>                             |
  |          Etienne Kneuss <colder@php.net>                             |
  +----------------------------------------------------------------------+

  $Id$
*/

/*
  PURPOSE:
	- Lint checks every DocBook <example> and <informalexample> in the
	  PHP Manual
  TODO:
	- Compare expected output with actual (so not just a lint check)
	- Compare all of phpdoc-all (with stripped comments)
	- Inspect EDITOR code, too hackish?
	- Consider move from regex to xmlreader or dom
	- Check other <title> conditions aside from <function>
	- Make Windows friendly, and consider passing in args to exec()
  NOTES:
	- It's possible to check one extension at a time, by passing in said
	  (any) directory
*/

// These usually demonstrate actual failures and the script needs to be improved
// (probably by checking the result) and not doing a linter check
const KNOWN_FILE_FAILURES = [
    '.manual.xml',
    // Just ignore the migration guides for the moment
    'appendices/',
    'language/basic-syntax.xml',
    'language/control-structures/alternative-syntax.xml',
    'language/control-structures/continue.xml',
    'language/control-structures/declare.xml',
    'language/control-structures/elseif.xml',
    'language/control-structures/goto.xml',
    'language/control-structures/match.xml',
    'language/enumerations.xml',
    'language/exceptions.xml',
    'language/functions.xml',
    'language/namespaces.xml',
    'language/oop5/basic.xml',
    'language/oop5/decon.xml',
    'language/oop5/final.xml',
    'language/oop5/inheritance.xml',
    'language/oop5/properties.xml',
    'language/operators.xml',
    'language/variables.xml',
    'language/types/array.xml',
    'language/types/declarations.xml',
    'language/types/string.xml',
    'reference/strings/functions/echo.xml',

    // Those need to be fixed
    // Do not run this script on programs which are not PHP
    'reference/yaf/yaf-loader.xml',
    'reference/yaf/yaf_application/construct.xml',
    'reference/yaf/yaf_router/addconfig.xml',
    // Uses ...
    'reference/ev/evwatcher/keepalive.xml',
    // Gets out of the CDATA to does a reference via methodname tag
    'reference/event/eventhttpconnection/setclosecallback.xml',
    // ignore for now but should be easy fix
    'reference/mbstring/functions/mb-decode-numericentity.xml',
    'reference/mbstring/functions/mb-encode-numericentity.xml',
];

const REGEX_EXAMPLE_FIND = '#<(informalexample|example)>\s*(?:<title>(.+)</title>)?\s*<programlisting(?: role="php")?>\s*<!\[CDATA\[(.+)]]>\s*</programlisting>(.*)</\1>#isU';
const REGEX_OUTPUT_FIND = '#&example\.outputs.*?;\s*<screen>\s*<!\[CDATA\[(.+)]]>\s*</screen>#isU';
const REGEX_TITLE_CLEAN = '#<function>([\w_-]+)</function>#';

$opts = getopt('p:t:o::h');

if (empty($opts) || isset($opts['h'])) {
	usage();
}
if (empty($opts['p']) || empty($opts['t'])) {
	echo "\nInfo: Both -p (phpdoc path) and -t (temporary directory) must be set\n";
	usage();
}

if (!is_dir($opts['p']) || !is_dir($opts['t'])) {
	trigger_error('Unknown directory passed in', E_USER_WARNING);
	usage();
}

$tmpdir = $opts['t'];
$path   = $opts['p'];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {

	$filename = $file->getPathname();
	
	if (!$file->isFile() || pathinfo($filename, PATHINFO_EXTENSION) !== 'xml' || is_known_failure($filename)) {
		continue;
	}

	$examples = get_examples($filename);

	if ($examples) {
		foreach ($examples as $example) {
			$parsed_example = validate_example($example, "$tmpdir/phplint{$_SERVER['REQUEST_TIME']}.php");

			if (isset($parsed_example['error_line_num'])) {
				$errors[] = $parsed_example;
			}
		}
	}
}

if (empty($errors)) {
	echo "No errors were found in examples!\n";
	exit(0);
} else {
	$o = '';
	foreach ($errors as $error) {
		echo "Filename      : $error[filename]\n";
		echo "Error         : $error[return]\n";
		echo "Line number   : $error[error_line_num]\n";
		echo "Example title : $error[title]\n";
		echo "Example type  : $error[type]\n";
		echo "Example num   : $error[num]\n";
		echo "Line -1       : " . $error['error_line'][0] . "\n";
		echo "Line          : " . $error['error_line'][1] . "\n";
		echo "Line +1       : " . $error['error_line'][2] . "\n";
		echo "-----------------------\n";
		$o .= " $error[filename]";
	}
	if (isset($opts['o'])) {
		if (empty($opts['o'])) {
			if (!empty($_SERVER['EDITOR'])) {
				$opts['o'] = $_SERVER['EDITOR'];
			} else {
				echo $o;
				exit;
			}
		}
		shell_exec($opts['o'] . $o);
	}
}

function get_examples(string $filename): array  {

	$content = file_get_contents($filename);

	$info = [];
	if ($number = preg_match_all(REGEX_EXAMPLE_FIND, $content, $matches)) {

		// Found at least one example
		foreach ($matches[3] as $num => $example) {

			// Expected output as suggested by the XML
			$expected = 'Unknown';
			if (preg_match(REGEX_OUTPUT_FIND, $matches[4][$num], $match)) {
				if (!empty($match[1])) {
					$expected = $match[1];
				}
			}
			// Type: example or informalexample
			$type = trim($matches[1][$num]);

			// Title: Most <example>'s have <title>'s
			$title = 'Unknown';
			if (!empty($matches[2][$num])) {
				$title = preg_replace(REGEX_TITLE_CLEAN, '\1()', $matches[2][$num]);
			}

			$info[] = [
                'title' 	=> trim(str_replace("\n", '', $title)),
                'filename'	=> trim($filename),
                'example'	=> trim($example),
                'expected'	=> trim($expected),
                'type'		=> trim($type),
                'num'		=> (int) $num + 1,
            ];
		}
	}

	return $info;
}

function validate_example ($info, $filename) {

	if (!file_exists($filename) && !touch($filename)) {
		trigger_error("Could not create file $filename for lint checking", E_USER_ERROR);
	}
	file_put_contents($filename, $info['example']);

	exec("php -l {$filename} 2>&1", $out, $return);

	$fq  = preg_quote($filename);
	
	$info['return'] = trim(preg_replace(array("@(in $fq.*)@", "@Errors parsing $fq@"), '', $out[0]));

	// Has it always been "No syntax errors detected"? Let's assume so for now
	if (!str_contains($info['return'], 'No syntax errors detected')) {
		$parts = explode("\n", $info['example']);
		
		$error_line_num = (int) trim(substr($out[0], strpos($out[0], 'on line')+8));

		$info['error_line_num'] = $error_line_num;
		$info['error_line'][0]  = trim($parts[($error_line_num-1)]);
		$info['error_line'][1]  = trim($parts[$error_line_num]);

		if (isset($parts[($error_line_num+1)])) {
			$info['error_line'][2] = trim($parts[($error_line_num+1)]);
		} else {
			$info['error_line'][2] = 'None';
		}
	}
	return $info;
}

function is_known_failure(string $filename): bool {
	foreach (KNOWN_FILE_FAILURES as $fail) {
		if (str_contains($filename, $fail)) {
			return true;
		}
	}
	return false;
}

function usage() {
	echo "\n------ DocBook Lint Checker ------\n";
	echo "Usage:   php {$_SERVER['argv'][0]} -p /path/to/php/doc/cvs -t /path/to/tmp/dir\n";
	echo "Example: php {$_SERVER['argv'][0]} -p /cvs/phpdoc/en -t /tmp\n";
	echo "Optional: -o to load files in EDITOR (" . $_SERVER['EDITOR'] . ") or -o /path/to/editor\n";
	exit;
}