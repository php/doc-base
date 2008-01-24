<?php // vim: ts=4 sw=4 et tw=78 fdm=marker

/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
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
  +----------------------------------------------------------------------+
  
  $Id$
*/

error_reporting(E_ALL);
$cvs_id = '$Id$';

echo "configure.php: $cvs_id\n";

function usage() // {{{
{
    global $acd;
    
    echo <<<HELPCHUNK
configure.php configures this package to adapt to many kinds of systems, and PhD
builds too.

Usage: ./configure [OPTION]...

Defaults for the options are specified in brackets.

Configuration:
  -h, --help                display this help and exit
  -V, --version             display version information and exit
  -q, --quiet, --silent     do not print `checking...' messages
      --srcdir=DIR          find the sources in DIR [configure dir or `.']

Package-specific:
  --enable-force-dom-save   force .manual.xml to be saved in a full build even
                            if it fails validation [{$acd['FORCE_DOM_SAVE']}]
  --enable-chm              enable Windows HTML Help Edition pages [{$acd['CHMENABLED']}]
  --enable-internals        include internals documentation [{$acd['INTERNALSENABLED']}]
  --with-php=PATH           Path to php CLI executable [detect]
  --with-phd=PATH           Path to phd [detect]
  --with-inipath=PATH       Path to php.ini file [@srcdir@/scripts]
  --with-lang=LANG          Language to build [{$acd['LANG']}]
  --with-partial=ID         Root ID to build [{$acd['PARTIAL']}]

HELPCHUNK;
} // }}}

function is_windows() {
    return strncmp(strtoupper(PHP_OS), "WIN", 3) === 0;
}

function checking($for) // {{{
{
    global $ac;
    
    if ($ac['quiet'] != 'yes') {
        echo "Checking {$for}... ";
        flush( STDOUT );
    }
} // }}}

function checkerror($msg) // {{{
{
    global $ac;
    
    if ($ac['quiet'] != 'yes') {
        echo "\n";
    }
    echo "error: {$msg}\n";
    exit(1);
} // }}}

function checkvalue($v) // {{{
{
    global $ac;
    
    if ($ac['quiet'] != 'yes') {
        echo "{$v}\n";
    }
} // }}}

function abspath($path) // {{{
{
    // realpath() doesn't return empty for empty on Windows
    if ($path == '') {
        return '';
    }
    return function_exists('realpath') ? realpath($path) : $path;
} // }}}

function quietechorun($e) // {{{
{
    // enclose in "" on Windows
    if (is_windows()) {
        $e = '"'.$e.'"';
    }
    if ($GLOBALS['ac']['quiet'] != 'yes') {
        echo "{$e}\n";
    }
    passthru($e);
} // }}}

function find_file($file_array) // {{{
{
    $paths = explode(PATH_SEPARATOR, getenv('PATH'));

    if (is_array($paths)) {
        foreach ($paths as $path) {
            foreach ($file_array as $name) {
                if (file_exists("{$path}/{$name}") && is_file("{$path}/{$name}")) {
                    return "{$path}/{$name}";
                }
            }
        }
    }

    return '';
} // }}}

// Recursive glob() with a callback function {{{
function globbetyglob($globber, $userfunc)
{
    foreach (glob("$globber/*") as $file) {
        if (is_dir($file)) {
            globbetyglob($file, $userfunc);
        } else {
            call_user_func($userfunc, $file);
        }
    }
} // }}}

function find_dot_in($filename) // {{{
{
    if (substr($filename, -3) == '.in') {
        $GLOBALS['infiles'][] = $filename;
    }
} // }}}

function generate_output_file($in, $out, $ac) // {{{
{
    $data = file_get_contents($in);

    if ($data === false) {
        return false;
    }
    foreach ($ac as $k => $v) {
        $data = preg_replace('/@' . preg_quote($k) . '@/', $v, $data);
    }

    return file_put_contents($out, $data);
} // }}}

function make_scripts_executable($filename) // {{{
{
    if (substr($filename, -3) == '.sh') {
        chmod($filename, 0755);
    }
} // }}}

// Loop through and print out all XML validation errors {{{
function print_xml_errors() {
    $errors = libxml_get_errors();
    if ($errors && count($errors) > 0) {
        $valid = true;
        foreach($errors as $err) {
            // Skip all XInclude errors
            if (!strpos($err->message, "include")) {
                $valid = false;

                $file = file(urldecode($err->file)); // libxml appears to urlencode() its errors strings
                $line = rtrim($file[$err->line - 1]);
                $padding = str_repeat("-", $err->column) . "^";

                printf("\nERROR (%s:%d)\n%s\n%s\n\t%s\n", $err->file, $err->line, $line, $padding, $err->message);
            }
        }

        if (!$valid) {
            echo "eyh man. No worries. Happ shittens. Try again after fixing the errors above\n";
            return true;
        }
    }
    libxml_clear_errors();
    return false;
} // }}}


$srcdir = ".";

// Settings {{{
$cygwin_php_bat = "{$srcdir}/../phpdoc-tools/php.bat";
$cygwin_phd_bat = "{$srcdir}/../phpdoc-tools/phd.bat";
$php_bin_names = array('php', 'php5', 'cli/php', 'php.exe', 'php5.exe', 'php-cli.exe', 'php-cgi.exe');
$phd_bin_names = array('phd', 'phd.exe');
$nsgmls_bin_names = array('nsgmls', 'onsgmls', 'nsgmls.exe', 'onsgmls.exe');
// }}}

// Reject old PHP installations {{{
if (phpversion() < 5) {
    echo "PHP 5 or above is required. Version detected: " . phpversion() . "\n";
    exit(100);
} else {
    echo "PHP version: " . phpversion() . "\n";
} // }}}

echo "\n";

$acd = array( // {{{
    'srcdir' => $srcdir,
    'quiet' => 'no',
    'WORKDIR' => $srcdir,
    'SRCDIR' => $srcdir,
    'PHP' => '',
    'PHD' => '',
    'INIPATH' => "{$srcdir}/scripts",
    'CHMENABLED' => 'no',
    'CHMONLY_INCL_BEGIN' => '<!--',
    'CHMONLY_INCL_END' => '-->',
    'INTERNALSENABLED' => 'yes',
    'INTERNALS_EXCL_BEGIN' => '',
    'INTERNALS_EXCL_END' => '',
    'LANG' => 'en',
    'LANGDIR' => "{$srcdir}/en",
    'PHP_BUILD_DATE' => date('Y-m-d'),
    'ENCODING' => 'utf-8',
    'FORCE_DOM_SAVE' => 'no',
    'PARTIAL' => 'no',

    // Junk to make the old scripts (file-entities.php and missing-entities.php) cooperative
    'PHP_SOURCE' => 'no',
    'PEAR_SOURCE' => 'no',
    'PECL_SOURCE' => 'no',
    'EXT_SOURCE' => 'no',
    'CYGWIN' => 'no',
    'WINJADE' => '0',
    'NSGMLS' => 'no',
    'SP_OPTIONS' => 'SP_ENCODING=XML SP_CHARSET_FIXED=YES',
); // }}}

$ac = $acd;

$srcdir_dependant_settings = array( 'INIPATH', 'LANGDIR' );
$overridden_settings = array();

foreach ($_SERVER['argv'] as $opt) { // {{{
    $parts = explode('=', $opt, 2);
    if (strncmp($opt, '--enable-', 9) == 0) {
        $o = substr($parts[0], 9);
        $v = 'yes';
    } else if (strncmp($opt, '--disable-', 10) == 0 || strncmp($opt, '--without-', 10) == 0) {
        $o = substr($parts[0], 10);
        $v = 'no';
    } else if (strncmp($opt, '--with-', 7) == 0) {
        $o = substr($parts[0], 7);
        $v = isset($parts[1]) ? $parts[1] : 'yes';
    } else if (strncmp($opt, '--', 2) == 0) {
        $o = substr($parts[0], 2);
        $v = isset($parts[1]) ? $parts[1] : 'yes';
    } else if ($opt[0] == '-') {
        $o = $opt[1];
        $v = substr($opt, 2);
    } else {
        continue;
    }
    
    $overridden_settings[] = strtoupper($o);
    switch ($o) {
        case 'h':
        case 'help':
            usage();
            exit();

        case 'V':
        case 'version':
            // Version/revision is always printed out
            exit();

        case 'q':
        case 'quiet':
        case 'silent':
            $ac['quiet'] = $v;
            break;

        case 'srcdir':
            foreach ($srcdir_dependant_settings as $s) {
                if (!in_array($s, $overridden_settings)) {
                    $ac[$s] = $v . substr($ac[$s], strlen($ac['srcdir']));
                }
            }
            $ac['srcdir'] = $v;
            $ac['SRCDIR'] = $v;
            $ac['WORKDIR'] = $v;
            break;

        case 'force-dom-save':
            $ac['FORCE_DOM_SAVE'] = $v;
            break;

        case 'chm':
            $ac['CHMENABLED'] = $v;
            break;

        case 'internals':
            $ac['INTERNALSENABLED'] = $v;
            break;

        case 'php':
            $ac['PHP'] = $v;
            break;

        case 'phd':
            $ac['PHD'] = $v;
            break;

        case 'inipath':
            $ac['INIPATH'] = $v;
            break;

        case 'lang':
            $ac['LANG'] = $v;
            break;

        case 'partial':
            $ac['PARTIAL'] = $v;
            break;
        
        default:
            echo "WARNING: Unknown option '{$o}'!\n";
            break;
    }
} // }}}

checking('for source directory');
if (!file_exists($ac['srcdir']) || !is_dir($ac['srcdir']) || !is_writable($ac['srcdir'])) {
    checkerror("Source directory doesn't exist or can't be written to.");
}
$ac['srcdir'] = abspath($ac['srcdir']);
checkvalue($ac['srcdir']);

checking('whether to save an invalid .manual.xml');
checkvalue($ac['FORCE_DOM_SAVE']);

checking('whether to include CHM');
$ac['CHMONLY_INCL_BEGIN'] = ($ac['CHMENABLED'] == 'yes' ? '' : '<!--');
$ac['CHMONLY_INCL_END'] = ($ac['CHMENABLED'] == 'yes' ? '' : '-->');
checkvalue($ac['CHMENABLED']);

checking("whether to include internals documentation");
$ac['INTERNALS_EXCL_BEGIN'] = ($ac['INTERNALSENABLED'] == 'yes' ? '' : '<!--');
$ac['INTERNALS_EXCL_END'] = ($ac['INTERNALSENABLED'] == 'yes' ? '' : '-->');
checkvalue($ac['INTERNALSENABLED']);

checking("for PHP executable");
if ($ac['PHP'] == '' || $ac['PHP'] == 'no') {
    $ac['PHP'] = find_file($php_bin_names);
} else if (file_exists($cygwin_php_bat)) {
    $ac['PHP'] = $cygwin_php_bat;
}

if ($ac['PHP'] == '') {
    checkerror("Could not find a PHP executable. Use --with-php=/path/to/php.");
}
if (!file_exists($ac['PHP']) || !is_executable($ac['PHP'])) {
    checkerror("PHP executable is invalid - how are you running configure? " .
               "Use --with-php=/path/to/php.");
}
$ac['PHP'] = abspath($ac['PHP']);
checkvalue($ac['PHP']);

checking("for PHD executable");
if ($ac['PHD'] == '' || $ac['PHD'] == 'no') {
    $ac['PHD'] = find_file($phd_bin_names);
} else if (file_exists($cygwin_phd_bat)) {
    $ac['PHD'] = $cygwin_phd_bat;
}

if ($ac['PHD'] == '') {
    checkerror("Could not find a PHD executable. Use --with-phd=/path/to/phd.");
}
if (!file_exists($ac['PHD']) && !is_readable($ac['PHD'])) { 
    checkerror("PHD file is invalid. Use --with-phd=/path/to/phd.");
}
$ac['PHD'] = abspath($ac['PHD']);
checkvalue($ac['PHD']);

checking("for PHP INI path");
if ($ac['INIPATH'] != '' && $ac['INIPATH'] != 'no') {
    if (!file_exists($ac['INIPATH']) || !is_readable($ac['INIPATH'])) {
        checkerror("INI path doesn't exist or isn't readable.");
    }
    $ac['INIPATH'] = abspath($ac['INIPATH']);
}
checkvalue($ac['INIPATH']);

checking("for language to build");
if ($ac['LANG'] == '' || $ac['LANG'] == 'no') {
    checkerror("Using '--with-lang=' or '--without-lang' is just going to cause trouble.");
} else if ($ac['LANG'] == 'yes') {
    $ac['LANG'] = 'en';
}
checkvalue($ac['LANG']);

checking("whether the language is supported");
$LANGDIR = "{$ac['srcdir']}/{$ac['LANG']}";
if (!file_exists($LANGDIR) || !is_readable($LANGDIR)) {
    checkerror("No language directory found.");
}

$ac['LANGDIR'] = basename($LANGDIR);
checkvalue("yes");

checking("for partial build");
checkvalue($ac['PARTIAL']);

// Do NOT add a commandline setting for this. We only support $ac[ 'NSGMLS' ] to
//  keep missing-entities.php.in working.
checking("for nsgmls");
if (!is_windows() && ($nsgmls = abspath(find_file($nsgmls_bin_names))) != '')
    $ac['NSGMLS'] = $nsgmls;
checkvalue($ac['NSGMLS']);

$infiles = array();
globbetyglob($ac['srcdir'], 'find_dot_in');

foreach ($infiles as $in) {
    $in = chop($in);

    if (basename($in) == 'configure.in') {
        continue;
    }

    $out = substr($in, 0, -3);
    echo "generating $out: ";
    if (generate_output_file($in, $out, $ac)) {
        echo "done\n";
    } else {
        echo "fail\n";
        exit(117);
    }
}

globbetyglob("{$ac['srcdir']}/scripts", 'make_scripts_executable');
file_put_contents("{$ac['srcdir']}/entities/phpweb.ent", '');

$ini = ($ac['INIPATH'] != '' && $ac['INIPATH'] != 'no') ? " -c \"{$ac['INIPATH']}\"" : '';
$redir = ($ac['quiet'] == 'yes') ? " > /dev/null" : '';
quietechorun("\"{$ac['PHP']}\"{$ini} -q \"{$ac['srcdir']}/scripts/file-entities.php\"{$redir}");
echo "file-entities.php is done.\n";
quietechorun("rm -f \"{$ac['srcdir']}/entities/missing*\"");
quietechorun("\"{$ac['PHP']}\"{$ini} -q \"{$ac['srcdir']}/scripts/missing-entities.php\"{$redir}");
echo "missing-entities.php is done.\n";

libxml_use_internal_errors(true);

$dom = new DOMDocument();
$didLoad = $dom->load( "{$ac['srcdir']}/manual.xml", LIBXML_DTDLOAD | LIBXML_NOENT );

// XXX: What errors can happen here that aren't fatal? Why do we continue only if we want a forced save?
if ($didLoad === false) {
    if (print_xml_errors() === true) {
        echo "These errors came from load(). We can't save a partial .manual.xml.\n";
        exit(1);
    }
} else if (print_xml_errors() === true && $ac['FORCE_DOM_SAVE'] == 'no') {
    echo "Not sure what happened here, but we're bailing!\n";
    exit(1);
}

$dom->xinclude();

if ($ac['PARTIAL'] != '' && $ac['PARTIAL'] != 'no') { // {{{
    $node = $dom->getElementById($ac['PARTIAL']);
    if (!$node) {
        exit("Failed to find partial ID in source XML: " . $ac['PARTIAL']);
    }
    if ($node->tagName !== 'book' && $node->tagName !== 'set') {
        // this node is not normally allowed here, attempt to wrap it
        // in something else
        $parents = array();
        switch ($node->tagName) {
            case 'refentry':
                $parents[] = 'reference';
                // Break omitted intentionally
            case 'part':
                $parents[] = 'book';
                break;
        }
        foreach ($parents as $name) {
            $newNode = $dom->createElement($name);
            $newNode->appendChild($node);
            $node = $newNode;
        }
    }
    $set = $dom->documentElement;
    $set->nodeValue = '';
    $set->appendChild($dom->createElement('title', 'PHP Manual (Partial)')); // prevent validate from complaining unnecessarily
    $set->appendChild($node);
    $dom->validate(); // we don't care if the validation works or not

    $filename = '.manual.' . $ac['PARTIAL'] . '.xml';
    $dom->save("{$ac['srcdir']}/{$filename}");
    echo "Partial manual saved to $filename, to build it run 'phd -d" . realpath($filename). "'\n";
    exit(0);
} // }}} 

if ($dom->validate()) {
    echo "All good.\n";
    $dom->save("{$ac['srcdir']}/.manual.xml");

    echo "All you have to do now is run 'phd -d " . realpath(".manual.xml") . "'\n";
    exit(0); // Tell the shell that this script finished successfully.
} else {
    if (print_xml_errors() === true) {
        if ($ac['FORCE_DOM_SAVE'] == 'yes') { 
            // Allow the .manual.xml file to be created, even if it is not valid.
            echo "Writing .manual.xml anyway\n";
            $dom->save("{$ac['srcdir']}/.manual.xml");
        } else {
            echo "Didn't write .manual.xml. Sorry.\n";
        }
    } else {
        echo "This doesn't seem quite right. Validation failed but there aren't any errors.\n";
    }
    exit(1); // Tell the shell that this script finished with an error.
}

?>
