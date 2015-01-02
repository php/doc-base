#!/usr/bin/env php
<?php // vim: ts=4 sw=4 et tw=78 fdm=marker

/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2014 The PHP Group                                |
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
  |             Hannes Magnusson <bjori@php.net>                         |
  |             Gwynne Raskind <gwynne@php.net>                          |
  +----------------------------------------------------------------------+
  
  $Id$
*/

error_reporting(-1);
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
  -h, --help                     Display this help and exit
  -V, --version                  Display version information and exit
  -q, --quiet, --silent          Do not print `checking...' messages
      --srcdir=DIR               Find the sources in DIR [configure dir or `.']
      --basedir                  Doc-base directory
                                 [{$acd['BASEDIR']}]
      --rootdir                  Root directory of SVN Doc checkouts
                                 [{$acd['ROOTDIR']}]

Package-specific:
  --enable-force-dom-save        Force .manual.xml to be saved in a full build
                                 even if it fails validation [{$acd['FORCE_DOM_SAVE']}]
  --enable-chm                   Enable Windows HTML Help Edition pages [{$acd['CHMENABLED']}]
  --enable-xml-details           Enable detailed XML error messages [{$acd['DETAILED_ERRORMSG']}]
  --disable-segfault-error       LIBXML may segfault with broken XML, use this
                                 if it does [{$acd['SEGFAULT_ERROR']}]
  --disable-segfault-speed       PHP (<5.3.7) will segfault during shutdown.
                                 Disabling that segfault causes performance
                                 issues. [{$acd['SEGFAULT_SPEED']}]
  --disable-version-files        Do not merge the extension specific
                                 version.xml files
  --disable-libxml-check         Disable the libxml 2.7.4+ requirement check
  --with-php=PATH                Path to php CLI executable [detect]
  --with-lang=LANG               Language to build [{$acd['LANG']}]
  --with-partial=my-xml-id       Root ID to build (e.g. <book xml:id="MY-ID">) [{$acd['PARTIAL']}]
  --disable-broken-file-listing  Do not ignore translated files in 
                                 broken-files.txt
  --redirect-stderr-to-stdout    Redirect STDERR to STDOUT. Use STDOUT as the
                                 standard output for XML errors [{$acd['STDERR_TO_STDOUT']}]
  --output=FILENAME              Save to given file (i.e. not .manual.xml)
                                 [{$acd['OUTPUT_FILENAME']}]
  --generate=FILENAME            Create an XML only for provided file

HELPCHUNK;
} // }}}

function errbox($msg) {
    $len = strlen($msg)+4;
    $line = "+" . str_repeat("-", $len) . "+";

    echo $line, "\n";
    echo "|  ", $msg, "  |", "\n";
    echo $line, "\n\n";
}
function errors_are_bad($status) {
    echo "\nEyh man. No worries. Happ shittens. Try again after fixing the errors above.\n";
    exit($status);
}

function is_windows() {
    return strncmp(strtoupper(PHP_OS), "WIN", 3) === 0;
}

function checking($for) // {{{
{
    global $ac;
    
    if ($ac['quiet'] != 'yes') {
        echo "Checking {$for}... ";
        flush();
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
    return str_replace('\\', '/', function_exists('realpath') ? realpath($path) : $path);
} // }}}

function quietechorun($e) // {{{
{
    // enclose in "" on Windows for PHP < 5.3
    if (is_windows() && phpversion() < '5.3') {
        $e = '"'.$e.'"';
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
        $data = str_replace("@$k@", $v, $data);
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
function print_xml_errors($details = true) {
    global $ac;
    $errors = libxml_get_errors();
    $output = ( $ac['STDERR_TO_STDOUT'] == 'yes' ) ? STDOUT : STDERR;
    if ($errors && count($errors) > 0) {
        foreach($errors as $err) {
                $errmsg = wordwrap(" " . trim($err->message), 80, "\n ");
                if ($details && $err->file) {
                    $file = file(urldecode($err->file)); // libxml appears to urlencode() its errors strings
                    if (isset($file[$err->line])) {
                        $line = rtrim($file[$err->line - 1]);
                        $padding = str_repeat("-", $err->column) . "^";
                        fprintf($output, "\nERROR (%s:%s:%s)\n%s\n%s\n%s\n", $err->file, $err->line, $err->column, $line, $padding, $errmsg);
                    } else {
                        fprintf($output, "\nERROR (%s:unknown)\n%s\n", $err->file, $errmsg);
                    }
                } else {
                    fprintf($output, "%s\n", $errmsg);
                }
                // Error too severe, stopping
                if ($err->level === LIBXML_ERR_FATAL) {
                    fprintf($output, "\n\nPrevious errors too severe. Stopping here.\n\n");
                    break;
                }
        }
    }
    libxml_clear_errors();
} // }}}

$srcdir  = dirname(__FILE__);
$workdir = $srcdir;
$basedir = $srcdir;
$rootdir = dirname($basedir);
if (basename($rootdir) == 'doc-base') {
    $rootdir = dirname($rootdir);
}

// Settings {{{
$cygwin_php_bat = "{$srcdir}/../phpdoc-tools/php.bat";
$php_bin_names = array('php', 'php5', 'cli/php', 'php.exe', 'php5.exe', 'php-cli.exe', 'php-cgi.exe');
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
    'basedir' => $basedir,
    'rootdir' => $rootdir,
    'workdir' => $workdir,
    'quiet' => 'no',
    'WORKDIR' => $srcdir,
    'SRCDIR' => $srcdir,
    'BASEDIR' => $basedir,
    'ROOTDIR' => $rootdir,
    'ONLYDIR' => "{$rootdir}/en",
    'PHP' => '',
    'CHMENABLED' => 'no',
    'CHMONLY_INCL_BEGIN' => '<!--',
    'CHMONLY_INCL_END' => '-->',
    'LANG' => 'en',
    'LANGDIR' => "{$rootdir}/en",
    'ENCODING' => 'utf-8',
    'FORCE_DOM_SAVE' => 'no',
    'PARTIAL' => 'no',
    'DETAILED_ERRORMSG' => 'no',
    'SEGFAULT_ERROR' => 'yes',
    'SEGFAULT_SPEED' => 'yes',
    'VERSION_FILES'  => 'yes',
    'LIBXML_CHECK' => 'yes',
    'USE_BROKEN_TRANSLATION_FILENAME' => 'yes',
    'OUTPUT_FILENAME' => $srcdir . '/.manual.xml',
    'GENERATE' => 'no',
    'STDERR_TO_STDOUT' => 'no',
    'INPUT_FILENAME'   => 'manual.xml',
    'TRANSLATION_ONLY_INCL_BEGIN' => '',
    'TRANSLATION_ONLY_INCL_END' => '',
); // }}}

$ac = $acd;

$srcdir_dependant_settings = array( 'LANGDIR' );
$overridden_settings = array();

foreach ($_SERVER['argv'] as $k => $opt) { // {{{
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
    } else if (strncmp($opt, '--redirect-', 11) == 0) {
        $o = substr($parts[0], 11);
        $v = 'yes';
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
            break;

        case 'force-dom-save':
            $ac['FORCE_DOM_SAVE'] = $v;
            break;

        case 'chm':
            $ac['CHMENABLED'] = $v;
            break;

        case 'php':
            $ac['PHP'] = $v;
            break;

        case 'lang':
            $ac['LANG'] = $v;
            break;

        case 'partial':
            if ($v == "yes") {
                if (isset($_SERVER['argv'][$k+1])) {
                    $val = $_SERVER['argv'][$k+1];
                    errbox("TYPO ALERT: Didn't you mean --{$o}={$val}?");
                } else {
                    errbox("TYPO ALERT: --partial without a chunk ID?");
                }
            }

            $ac['PARTIAL'] = $v;
            break;

        case 'xml-details':
            $ac['DETAILED_ERRORMSG'] = $v;
            break;

        case 'segfault-error':
            $ac['SEGFAULT_ERROR'] = $v;
            break;

        case 'segfault-speed':
            $ac['SEGFAULT_SPEED'] = $v;
            break;

        case 'version-files':
            $ac['VERSION_FILES'] = $v;
            break;

        case 'libxml-check':
            $ac['LIBXML_CHECK'] = $v;
            break;

        case 'rootdir':
            $ac['rootdir'] = $v;
            break;

        case 'basedir':
            $ac['basedir'] = $v;
            break;
        
        case 'output':
            $ac['OUTPUT_FILENAME'] = $v;
            break;

        case 'generate':
            $ac['GENERATE'] = $v;
            break;

        case 'broken-file-listing':
            $ac['USE_BROKEN_TRANSLATION_FILENAME'] = $v;

        case 'stderr-to-stdout':
            $ac['STDERR_TO_STDOUT'] = $v;
            break;
            
        case '':
            break;

        default:
            echo "WARNING: Unknown option '{$o}'!\n";
            break;
    }
} // }}}

// Reject 'old' LibXML installations, due to LibXML feature #502960 {{{
if (version_compare(LIBXML_DOTTED_VERSION, '2.7.4', '<') && $ac['LIBXML_CHECK'] === 'yes') {
	echo "LibXML 2.7.4+ added a 'feature' to break things, typically namespace related, and unfortunately we must require it.\n";
	echo "For a few related details, see: http://www.mail-archive.com/debian-bugs-dist@lists.debian.org/msg777646.html\n";
	echo "Please recompile PHP with a LibXML version 2.7.4 or greater. Version detected: " . LIBXML_DOTTED_VERSION . "\n";
	echo "Or, pass in --disable-libxml-check if doing so feels safe.\n\n";
	#exit(100);
} // }}}

checking('for source directory');
if (!file_exists($ac['srcdir']) || !is_dir($ac['srcdir']) || !is_writable($ac['srcdir'])) {
    checkerror("Source directory doesn't exist or can't be written to.");
}
$ac['SRCDIR'] = $ac['srcdir'];
$ac['WORKDIR'] = $ac['srcdir'];
$ac['ROOTDIR'] = $ac['rootdir'];
$ac['BASEDIR'] = $ac['basedir'];
checkvalue($ac['srcdir']);

checking('for output filename');
checkvalue($ac['OUTPUT_FILENAME']);

checking('whether to include CHM');
$ac['CHMONLY_INCL_BEGIN'] = ($ac['CHMENABLED'] == 'yes' ? '' : '<!--');
$ac['CHMONLY_INCL_END'] = ($ac['CHMENABLED'] == 'yes' ? '' : '-->');
checkvalue($ac['CHMENABLED']);

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

checking("for language to build");
if ($ac['LANG'] == '' /* || $ac['LANG'] == 'no' */) {
    checkerror("Using '--with-lang=' or '--without-lang' is just going to cause trouble.");
} else if ($ac['LANG'] == 'yes') {
    $ac['LANG'] = 'en';
}
if ($ac["LANG"] == "en") {
    $ac["TRANSLATION_ONLY_INCL_BEGIN"] = "<!--";
    $ac["TRANSLATION_ONLY_INCL_END"] = "-->";
}
checkvalue($ac['LANG']);

checking("whether the language is supported");
$LANGDIR = "{$ac['rootdir']}/{$ac['LANG']}";
if (file_exists("{$LANGDIR}/trunk")) {
    $LANGDIR .= '/trunk';
}
if (!file_exists($LANGDIR) || !is_readable($LANGDIR)) {
    checkerror("No language directory found.");
}

$ac['LANGDIR'] = basename($LANGDIR);
if ($ac['LANGDIR'] == 'trunk') {
    $ac['LANGDIR'] = '../' . basename(dirname($LANGDIR)) . '/trunk';
    $ac['EN_DIR'] = '../en/trunk';
} else {
    $ac['EN_DIR'] = 'en';
}
checkvalue("yes");

checking("for partial build");
checkvalue($ac['PARTIAL']);

checking('whether to enable detailed XML error messages');
checkvalue($ac['DETAILED_ERRORMSG']);

checking('libxml version');
checkvalue(LIBXML_DOTTED_VERSION);

checking('whether to enable detailed error reporting (may segfault)');
checkvalue($ac['SEGFAULT_ERROR']);
checking('whether to optimize out the DTD (performance gain, but segfaults)');
checkvalue($ac['SEGFAULT_SPEED']);

if ($ac["GENERATE"] != "no") {
    $ac["ONLYDIR"] = dirname(realpath($ac["GENERATE"]));
}


// We shouldn't be globbing for this. autoconf requires you to tell it which files to use, we should do the same
// Notice how doing it this way results in generating less than half as many files.
$infiles = array(
    'manual.xml.in',
    'install-unix.xml.in',
    'install-win.xml.in',
    'developer.template.xml.in',
    'scripts/file-entities.php.in',
);

foreach ($infiles as $in) {
    $in = chop("{$ac['basedir']}/{$in}");

    $out = substr($in, 0, -3);
    echo "Generating {$out}... ";
    if (generate_output_file($in, $out, $ac)) {
        echo "done\n";
    } else {
        echo "fail\n";
        errors_are_bad(117);
    }
}

if ($ac['SEGFAULT_ERROR'] === 'yes') {
    libxml_use_internal_errors(true);
}

$compact = defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0;
$LIBXML_OPTS = LIBXML_NOENT | $compact;

if ($ac['VERSION_FILES'] === 'yes') {
    $dom = new DOMDocument;
    $dom->preserveWhitespace = false;
    $dom->formatOutput       = true;

    $tmp = new DOMDocument;
    $tmp->preserveWhitespace = false;

    $versions = $dom->appendChild($dom->createElement("versions"));


    echo "Iterating over extension specific version files... ";
    if ($ac["GENERATE"] != "no") {
        $globdir = dirname($ac["GENERATE"]) . "/{../,./}versions.xml";
    }
    else {
        if (file_exists($ac['rootdir'] . '/en/trunk')) {
            $globdir = $ac['rootdir'] . '/en/trunk';
        } else {
            $globdir = $ac['rootdir'] . '/en';
        }
        $globdir .= "/*/*/versions.xml";
    }
    foreach(glob($globdir, GLOB_BRACE) as $file) {
        if($tmp->load($file)) {
            foreach($tmp->getElementsByTagName("function") as $function) {
                $function = $dom->importNode($function, true);
                $versions->appendChild($function);
            }
        } else {
            print_xml_errors();
            errors_are_bad(1);
        }
    }
    echo "OK\n";
    echo "Saving it... ";

    if ($dom->save($ac['srcdir'] . '/version.xml')) {
        echo "OK\n";
    } else {
        echo "FAIL!\n";
    }
}

globbetyglob("{$ac['basedir']}/scripts", 'make_scripts_executable');

$redir = ($ac['quiet'] == 'yes') ? ' > ' . (is_windows() ? 'nul' : '/dev/null') : '';

quietechorun("\"{$ac['PHP']}\" -q \"{$ac['basedir']}/scripts/file-entities.php\"{$redir}");


checking("for if we should generate a simplified file");
if ($ac["GENERATE"] != "no") {
    if (!file_exists($ac["GENERATE"])) {
        checkerror("Can't find {$ac["GENERATE"]}");
    }
    $tmp = realpath($ac["GENERATE"]);
    $ac["GENERATE"] = str_replace($ac["ROOTDIR"].$ac["LANGDIR"], "", $tmp);
    $str = "\n<!ENTITY developer.include.file SYSTEM 'file:///{$ac["GENERATE"]}'>";
    file_put_contents("{$ac["basedir"]}/entities/file-entities.ent", $str, FILE_APPEND);
    $ac["INPUT_FILENAME"] = "developer.template.xml";
    $ac["FORCE_DOM_SAVE"] = "yes";
}
checkvalue($ac["GENERATE"]);

checking('whether to save an invalid .manual.xml');
checkvalue($ac['FORCE_DOM_SAVE']);

echo "Loading and parsing {$ac["INPUT_FILENAME"]}... ";
flush();

$dom = new DOMDocument();

// realpath() is important: omitting it causes severe performance degradation
// and doubled memory usage on Windows.
$didLoad = $dom->load(realpath("{$ac['srcdir']}/{$ac["INPUT_FILENAME"]}"), $LIBXML_OPTS);

// Check if the XML was simply broken, if so then just bail out
if ($didLoad === false) {
    echo "failed.\n";
    print_xml_errors();
    errors_are_bad(1);
}

echo "done.\n";
echo "Validating {$ac["INPUT_FILENAME"]}... ";
flush();

$dom->xinclude();
print_xml_errors();

if ($ac['PARTIAL'] != '' && $ac['PARTIAL'] != 'no') { // {{{
    $dom->validate(); // we don't care if the validation works or not
    $node = $dom->getElementById($ac['PARTIAL']);
    if (!$node) {
        echo "failed.\n";
        echo "Failed to find partial ID in source XML: {$ac['PARTIAL']}\n";
        errors_are_bad(1);
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

    $filename = "{$ac['srcdir']}/.manual.{$ac['PARTIAL']}.xml";
    $dom->save($filename);
    echo "done.\n";
    echo "Partial manual saved to {$filename}. To build it, run 'phd -d {$filename}'\n";
    exit(0);
} // }}} 

$mxml = $ac["OUTPUT_FILENAME"];
if ($dom->validate()) {
    echo "done.\n";
    printf("\nAll good. Saving %s... ", basename($ac["OUTPUT_FILENAME"]));
    flush();
    if ($ac["SEGFAULT_SPEED"] == "yes") {
        $t = $dom->doctype;
        $dom->removeChild($t);
    }
    $dom->save($mxml);

    echo "done.\n";
    echo "All you have to do now is run 'phd -d {$mxml}'\n";
    echo "If the script hangs here, you can abort with ^C.\n";
    echo <<<CAT
		 _ _..._ __
		\)`    (` /
		 /      `\
		|  d  b   |
		=\  Y    =/--..-="````"-.
		  '.=__.-'               `\
		     o/                 /\ \
		      |                 | \ \   / )
		       \    .--""`\    <   \ '-' /
		      //   |      ||    \   '---'
		 jgs ((,,_/      ((,,___/


CAT;

    if (function_exists('proc_nice')) {
        echo " (Run `nice php configure.php` next time!)\n";
    }
    if ($ac["SEGFAULT_SPEED"] == "yes" && version_compare(PHP_VERSION, "5.3.7-dev", "lt")) {
        $b = basename($mxml);
        echo "\n\nPHP will segfault now :) - Don't worry though, the $b has been saved :D\n";
    }

    exit(0); // Tell the shell that this script finished successfully.
} else {
    echo "failed.\n";
    echo "\nThe document didn't validate, ";

    // Allow the .manual.xml file to be created, even if it is not valid.
    if ($ac['FORCE_DOM_SAVE'] == 'yes') { 
        printf("writing %s anyway, and ", basename($ac["OUTPUT_FILENAME"]));
        if ($ac["SEGFAULT_SPEED"] == "yes") {
            $t = $dom->doctype;
            $dom->removeChild($t);
        }
        $dom->save($mxml);
    }

    if ($ac['DETAILED_ERRORMSG'] == 'yes') {
        echo "trying to figure out what went wrong...\n";
        echo "(This could take awhile. If you experience segfaults here, try again with --disable-xml-details)\n";
        libxml_clear_errors(); // Clear the errrors, they contain incorrect filename&linenr

        $dom->load("{$ac['srcdir']}/{$ac["INPUT_FILENAME"]}", $LIBXML_OPTS | LIBXML_DTDVALID);
        print_xml_errors();
    } else {
        echo "here are the errors I got:\n";
        echo "(If this isn't enough information, try again with --enable-xml-details)\n";
        print_xml_errors(false);
    }

    // Exit normally when don't care about validation
    if ($ac["FORCE_DOM_SAVE"] == "yes") {
        if ($ac["SEGFAULT_SPEED"] == "yes" && version_compare(PHP_VERSION, "5.3.7-dev", "lt")) {
            $b = basename($mxml);
            echo "\n\nPHP will segfault now :) - Don't worry though, the $b has been saved :D\n";
        }

        exit(0);
    }

    errors_are_bad(1); // Tell the shell that this script finished with an error.
}
?>

