#!/usr/bin/php -q
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
  | Authors:    Nuno Lopes <nlopess@php.net>                             |
  |             George Peter Banyard <girgias@php.net>                   |
  +----------------------------------------------------------------------+

  $Id$
*/


if ($argc > 3 || $argc < 2 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>
  Find which files use the specified entity.

  Usage:
  <?php echo $argv[0];?> <entity> [<language-code>]

  <entity> is the entity you want to search.

  <language-code> must be a valid language code used in the repository, or
  'all' for all languages. Defaults to 'all'.

<?php
  exit;
}

// CONFIG SECTION
// Main directory of the PHP documentation (two directories up in the structure)
$docdir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;

/*********************************************************************/
/* Nothing to modify below this line                                 */
/*********************************************************************/

global $usage;

// Long runtime
set_time_limit(0);

// Default values
$langcodes = [
    'de',
    'en',
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

// Parameter value copying
if ($argc == 3) {
    if ($argv[2] !== 'all') {
        $langcodes = [$argv[2]];
    }
}

/*********************************************************************/
/* Here starts the functions part                                    */
/*********************************************************************/

// Checks a directory of phpdoc XML files
function check_dir($dir, $entity)
{
    // Collect files and directories in these arrays
    $directories = [];
    $files = [];
    
    // Skip old and unused functions directories (theoretically
    // it should only be in the English tree, but we are smart
    // and check for other language trees too...)
    if (preg_match("!/([a-z]{2}|pt_BR)/functions!", $dir)) {
        return;
    }
    
    // Open and traverse the directory
    $handle = @opendir($dir);
    while ($file = @readdir($handle)) {
      
      // Collect directories and XML files
      if ($file != 'CVS' && $file != '.' &&
          $file != '..' && is_dir($dir.$file)) {
        $directories[] = $file;
      }
      elseif (strstr($file, ".xml")) {
        $files[] = $file;
      }

    }
    @closedir($handle);
      
    // Sort files and directories
    sort($directories);
    sort($files);
      
    // Files first...
    foreach ($files as $file) {
      check_file($dir.$file, $entity);
    }

    // then the subdirs
    foreach ($directories as $file) {
      check_dir($dir.$file."/", $entity);
    }
} // check_dir() function end

function check_file ($filename, $entity)
{
    global $usage;

    // Read in file contents
    $contents = file_get_contents($filename);
    
    // Find all entity usage in this file
    if (preg_match("/&$entity;/U", $contents) == 1) {
        echo $filename . "\n";
        $usage++;
    }

} // check_file() function end
  
/*********************************************************************/
/* Here starts the program                                           */
/*********************************************************************/

// Checking all languages
foreach ($langcodes as $langcode) {

    $usage = 0;

    // Check for directory validity
    if (!@is_dir($docdir . $langcode)) {
        echo "The $langcode language code is not valid\n";
        continue;
    }
      
    // If directory is OK, start with the header
    echo "\nSearching in $docdir$langcode ...\n";
    
    // Check the requested directory
    check_dir("$docdir$langcode/", $argv[1]);

    echo "\nFiles found: $usage\n\n";

}

echo "Done!\n";
