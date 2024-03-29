#!/usr/bin/php -q
<?php
/*
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2023 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | https://www.php.net/license/3_01.txt.                                |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net, so we can mail you a copy immediately.              |
  +----------------------------------------------------------------------+
  | Authors:    Gabor Hojtsy <goba@php.net>                              |
  |             Gina Peter Banyard <girgias@php.net>                     |
  +----------------------------------------------------------------------+
 
  $Id$
*/

if ($argc > 3 || (isset($argv[1]) && in_array($argv[1], array('--help', '-help', '-h', '-?')))) {
?>

Find entity usage in phpdoc xml files and
list used and unused entities.

  Usage:
  <?php echo $argv[0];?> [<entity-file>] [<language-code>]

  <entity-file> must be a file name (with relative
  path from the phpdoc root) to a file containing
  <!ENTITY...> definitions. Defaults to 'base/entities/global.ent',
      'en/language-defs.ent, and 'en/language-snippets.ent'.

  <language-code> must be a valid language code used in the repository, or
  'all' for all languages. Defaults to 'all'.

  The script will generate an entity_usage.txt
  file, containing the entities defined in the
  <entity-file>.
  
<?php
  exit;
}

// CONFIG SECTION
// Main directory of the PHP documentation (two directories up in the structure)
$docdir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;

/*********************************************************************/
/* Nothing to modify below this line                                 */
/*********************************************************************/

// Long runtime
set_time_limit(0);

// Debug array
$debug = [];

// Array to collect the entities
$defined_entities = [];

// Default values
$langcodes = [
    'en',
    'de',
    'es',
    'fr',
    'it',
    'ja',
    'pl',
    'pt_br',
    'ro',
    'ru',
    'tr',
    'zh'
];
$files = ['base/entities/global.ent', 'en/language-defs.ent', 'en/language-snippets.ent'];

// Parameter value copying
if ($argc == 3) {
    if ($argv[2] !== 'all') {
        $langcodes = [$argv[2]];
    }
}

if ($argc >= 2) {
    $files = [$argv[1]];
}
  
/*********************************************************************/
/* Here starts the functions part                                    */
/*********************************************************************/

// Extract the entity names from the file
function extract_entity_definitions ($filename, &$entities)
{
    global $debug;
    // Read in the file, or die
    $file_array = file ($filename);
    if (!$file_array) { die ("Cannot open entity file ($filename)."); }
    
    // The whole file in a string
    $file_string = preg_replace("/[\r\n]/", "", join ("", $file_array));
    
    // Find entity names
    preg_match_all("/<!ENTITY\s+(.*)\s+/U", $file_string, $entities_found);
    $entities_found = $entities_found[1];

    // Convert to hash
    foreach ($entities_found as $entity_name) {
        if (array_key_exists($entity_name, $entities)) {
            $debug[] = "$entity_name is redefined in $filename";
        }
        $entities[$entity_name] = [];
    }
} // extract_entity_definitions() function end

function entities_list_to_regex(array $entities): string
{
    $entities_found = array_keys($entities);
    return "&(" . join("|", $entities_found) . ");";
}

// Checks a directory of phpdoc XML files
function check_dir($dir, &$defined_entities, $entity_regexp)
{
    // Collect files and directories in these arrays
    $directories = [];
    $files = [];
    
    // Open and traverse the directory
    $handle = @opendir($dir);
    while ($file = @readdir($handle)) {
        // Collect directories and XML files
        if ($file != '.git' && $file != '.' && $file != '..' && is_dir($dir.$file)) {
            $directories[] = $file;
        } elseif (strstr($file, ".xml")) {
            $files[] = $file;
        } elseif ($file === 'language-snippets.ent') {
            // Check usage of entities in language snippets too
            $files[] = $file;
        }
    }
    @closedir($handle);
      
    // Sort files and directories
    sort($directories);
    sort($files);
      
    // Files first...
    foreach ($files as $file) {
        check_file($dir.$file, $defined_entities, $entity_regexp);
    }

    // than the subdirs
    foreach ($directories as $file) {
        check_dir($dir.$file."/", $defined_entities, $entity_regexp);
    }
} // check_dir() function end

function check_file ($filename, &$defined_entities, $entity_regexp)
{
    global $debug;
    // Read in file contents
    $contents = preg_replace("/[\r\n]/", "", join("", file($filename)));
    
    // Find all entity usage in this file
    preg_match_all("/$entity_regexp/U", $contents, $entities_found);
    
    // No entities found
    if (count($entities_found[1]) == 0) { return; }
    
    // New occurrences found, so increase the number
    foreach ($entities_found[1] as $entity_name) {
        if (!array_key_exists($entity_name, $defined_entities)) {
            $debug[] = "Entity $entity_name has been found without any definition";
            continue;
        }
        $defined_entities[$entity_name][] = $filename;
    }

} // check_file() function end
  
/*********************************************************************/
/* Here starts the program                                           */
/*********************************************************************/

// Get entity definitions
foreach ($files as $file) {
    echo "Registering entities defined in '$file'\n";
    extract_entity_definitions($docdir . $file, $defined_entities);
}

echo "Found " . count($defined_entities) . " entities to check \n";

$entity_regexp = entities_list_to_regex($defined_entities);

// Checking all languages
foreach ($langcodes as $langcode) {

    // Check for directory validity
    if (!@is_dir($docdir . $langcode)) {
        $debug[] = "The $langcode language code is not valid";
        continue;
    } else {
        $tested_trees[] = $langcode;
    }
      
    // If directory is OK, start with the header
    echo "Searching in $docdir$langcode ...\n";
    
    // Check the requested directory
    check_dir("$docdir$langcode/", $defined_entities, $entity_regexp);

}
    
echo "Generating entity_usage.txt ...\n";
    
$fp = fopen("entity_usage.txt", "w");
fwrite($fp, "ENTITY USAGE STATISTICS

=========================================================
In this file you can find entity usage stats compiled
from the entity file: $file. The entity usage
was tested in the following tree[s] at phpdoc:\n" .
join(", ", $tested_trees) . ".

You may find many unused entities here. Please do
not delete the entities, unless you make sure, no
translation makes use of the entity. The purpose
of this statistics is to reduce the number of unused
entities in phpdoc. Here comes the numbers and file
names:
=========================================================

");

foreach ($defined_entities as $entity_name => $files) {
    $num = count($files);
    if ($num == 0) { $prep = "++ "; } else { $prep = "   "; }
    fwrite($fp, $prep . sprintf("%-30s %d", $entity_name, count($files)). "\n");
}

fclose($fp);

if ($debug !== []) {
    echo 'Several issues are present:', \PHP_EOL;
    print_r($debug);
    exit(1);
}

echo "Done!\n";
