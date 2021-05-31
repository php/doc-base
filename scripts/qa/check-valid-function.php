<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 8                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2021 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.0 of the PHP license,       |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_0.txt.                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors:    Dave Barr <dave@php.net>                                 |
  |             George Peter Banyard <girgias@php.net>                   |
  +----------------------------------------------------------------------+
  | Description: This file parses the manual and outputs all erroneous   |
  |              <function> tag usage.                                   |
  +----------------------------------------------------------------------+

*/

/** TODO
 * - Handle Class and methods
 */

/* Path to the root of EN extension reference tree */
$doc_en_root = dirname(__DIR__, 3) . '/en/reference';

$total = 0;

/* make a function list from files in the functions/ directories */
function make_func_list(string $lang_doc_root): array
{
    /* initialize array and declare some language constructs */
    $functions = [
        'include' => true,
        'include_once' => true,
        'require' => true,
        'require_once' => true,
        'return' => true,
    ];

    foreach (new DirectoryIterator($lang_doc_root) as $extensions) {
        if ($extensions->isDot() || !$extensions->isDir() || !$extensions->isReadable()) {
            continue;
        }

        foreach (new DirectoryIterator($extensions->getPathname()) as $extension) {
            if ($extension->isDot() || !$extension->isDir() || !$extension->isReadable()) {
                continue;
            }
            if ($extension->getFilename() !== 'functions') {
                continue;
            }

            foreach (new DirectoryIterator($extension->getPathname()) as $file) {
                if ($file->isDot() || !$file->isReadable()) {
                    continue;
                }
                $function = str_replace(['-', '.'], '_', $file->getBasename('.xml'));
                $functions[$function] = true;
            }
        }
    }

    return $functions;
}

echo "Building a list of functions...\n";
$functions =  make_func_list($doc_en_root);

echo 'List complete. ' . count($functions) . " functions.\n";

echo "Checking the manual for <function> tags that contain invalid functions...\n";
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($doc_en_root)) as $file) {
    if ($file->isDir() || !$file->isReadable()) {
        continue;
    }

    $name = $file->getBasename();
    $path = $file->getPathname();
    $contents = file_get_contents($path);

    if ($contents == '') {
        continue;
    }

    if (preg_match_all('|<function>(.*?)</function>|s', $contents, $m)
        && is_array($m)
        && is_array($m[1]))
    {
        foreach ($m[1] as $func) {
            //$func = strtolower(str_replace(array('::', '->'), '_', trim($func)));
            $func = trim($func);

            if (!\array_key_exists($func, $functions)) {
                $total++;
                $fileout = substr($file, strlen($doc_en_root) + 1);

                printf("%-60.60s  <function>$func</function>\n", $fileout);
            }
        }
    }
}
echo "Found $total occurrences.\n";

exit((bool) $total);
