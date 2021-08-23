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
  | Authors:    George Peter Banyard <girgias@php.net>                   |
  +----------------------------------------------------------------------+
  | Description: This file parses the manual and outputs all erroneous   |
  |              <methodname> tag usage.                                 |
  +----------------------------------------------------------------------+

*/

/* Path to the root of EN extension reference tree */
$doc_en_root = dirname(__DIR__, 3) . '/en/reference';

$total = 0;


function isMagicMethod(string $method): bool
{
    switch ($method) {
        case 'construct':
        case 'destruct':
        case 'tostring':
        case 'call':
        case 'callstatic':
        case 'wakeup':
        case 'sleep':
        // case 'set': // TODO This conflicts with certain classes
        // case 'get': // TODO This conflicts with certain classes
        case 'isset':
        case 'unset':
        case 'setstate':
        case 'clone':
            return true;
        default:
            return false;
    }
}

// TODO Need to add Predefined Interfaces/Classes
/* make a method list from files in extension directories */
function make_method_list(string $lang_doc_root): array
{
    $methods = [];

    foreach (new FilesystemIterator($lang_doc_root) as $extensions) {
        if (!$extensions->isDir() || !$extensions->isReadable()) {
            continue;
        }

        foreach (new FilesystemIterator($extensions->getPathname()) as $extension) {
            if (!$extension->isDir() || !$extension->isReadable()) {
                continue;
            }
            // Skip functions folder
            if ($extension->getFilename() === 'functions') {
                continue;
            }

           $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extension->getPathname(),
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO));

            foreach ($it as $file) {
                if ($file->isDir() || !$file->isReadable()) {
                    continue;
                }
                $class = str_replace($extension->getPath() . '/', '', $file->getPath());
                $class = str_replace('/', '\\', $class);
                //$class = str_replace('-', '_', $class);
                $class = strtolower($class);
                $method = str_replace(['-', '.'], '_', $file->getBasename('.xml'));
                $method = strtolower($method);
                if (isMagicMethod($method)) { $method = '__' . $method; }
                $fqn = $class . '::' . $method;
                $methods[$fqn] = true;
            }
        }
    }

    return $methods;
}

echo "Building a list of methods...\n";
$methods =  make_method_list($doc_en_root);

echo 'List complete. ' . count($methods) . " methods.\n";

echo "Checking the manual for <methodname> tags that contain invalid methods...\n";
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($doc_en_root)) as $file) {
    if ($file->isDir() || !$file->isReadable()) {
        continue;
    }

    $isFunction = false;

    $name = $file->getBasename();
    $path = $file->getPathname();
    if (strpos($path, '/functions/') !== false) { $isFunction = true; }
    $contents = file_get_contents($path);

    if ($contents == '') {
        continue;
    }

    if (preg_match_all('|<methodname>(.*?)</methodname>|s', $contents, $m)
        && is_array($m)
        && is_array($m[1]))
    {
        if ($isFunction) {
            // Unset the first entry as it is the function prototype
            unset($m[1][0]);
        }
        foreach ($m[1] as $fqn) {
            $lcFqn = strtolower(trim($fqn));
            if (!\array_key_exists($lcFqn, $methods)) {
                //var_dump($fqn);
                $total++;
                $fileout = substr($file, strlen($doc_en_root) + 1);

                printf("%-60.60s  <methodname>$fqn</methodname>\n", $fileout);
            }
        }
    }
}
echo "Found $total occurrences.\n";

exit((bool) $total);
