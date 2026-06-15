#!/usr/bin/env php
<?php // vim: ts=4 sw=4 et tw=78 fdm=marker
/*
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2026 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | https://www.php.net/license/3_01.txt.                                |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net, so we can mail you a copy immediately.              |
  +----------------------------------------------------------------------+
  | Authors:    Dave Barr <dave@php.net>                                 |
  |             Hannes Magnusson <bjori@php.net>                         |
  |             Gwynne Raskind <gwynne@php.net>                          |
  |             André L F S Bacci <ae@php.net>                           |
  +----------------------------------------------------------------------+
*/

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );

ob_implicit_flush();
libxml_use_internal_errors(true);

echo "configure.php on PHP " . phpversion() . ", libxml " . LIBXML_DOTTED_VERSION . "\n\n";

// general structure/ordering for refactoring this code

// init_parse()     // todo: argv parsing into a typed static class, remove all global
// init_check()     // todo: move all checks in one place
// init_usage()
// git_clean()                  done
// git_status()                 done
// dtd_conf_entities()          done
// dtd_file_entities()          done
// dom_load/save()              done
// xinclude_byid()              done
// xinclude_xpointer()          done
// xinclude_residua()           done
// xml_partial_output
// xml_validate()               done
// phd_acronym()                done
// phd_sources()                done
// phd_version()                done
// php_history()                done

const RNG_SCHEMA_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'docbook' . DIRECTORY_SEPARATOR . 'docbook-5.2.1' . DIRECTORY_SEPARATOR . 'rng' . DIRECTORY_SEPARATOR;
const RNG_SCHEMA_FILE = RNG_SCHEMA_DIR . 'docbook.rng';
const RNG_SCHEMA_XINCLUDE_FILE = RNG_SCHEMA_DIR . 'docbookxi.rng';

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
  --enable-chm                   Enable Windows HTML Help Edition pages [{$acd['CHMENABLED']}]
  --enable-xml-details           Enable detailed XML error messages [{$acd['DETAILED_ERRORMSG']}]
  --disable-version-files        Do not merge the extension specific
                                 version.xml files
  --disable-sources-file         Do not generate sources.xml file
  --disable-history-file         Do not copy file modification history file
  --disable-libxml-check         Disable the libxml 2.7.4+ requirement check
  --with-php=PATH                Path to php CLI executable [detect]
  --with-lang=LANG               Language to build [{$acd['LANG']}]
  --with-partial=my-xml-id       Root ID to build (e.g. <book xml:id="MY-ID">) [{$acd['PARTIAL']}]
  --disable-broken-file-listing  Do not ignore translated files in
                                 broken-files.txt
  --disable-xpointer-reporting   Do not show XInclude/XPointer failures. Only effective
                                 on translations
  --redirect-stderr-to-stdout    Redirect STDERR to STDOUT. Use STDOUT as the
                                 standard output for XML errors [{$acd['STDERR_TO_STDOUT']}]
  --output=FILENAME              Save to given file (i.e. not .manual.xml)
                                 [{$acd['OUTPUT_FILENAME']}]
  --generate=FILENAME            Create an XML only for provided file

HELPCHUNK;
} // }}}

function realpain( string $path , bool $touch = false , bool $mkdir = false ) : string
{
    // pain is real

    // care for external XML tools (realpath() everywhere)
    // care for Windows builds (forward slashes everywhere)
    // avoid `cd` and chdir() like the plague

    $path = str_replace( "\\" , '/' , $path );

    if ( $mkdir && ! file_exists( $path ) )
        mkdir( $path , recursive: true );

    if ( $touch && ! file_exists( $path ) )
        touch( $path );

    $res = realpath( $path );
    if ( is_string( $res ) )
        $path = str_replace( "\\" , '/' , $res );

    return $path;
}

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

function checking($for) // {{{
{
    global $ac;

    if ($ac['quiet'] != 'yes') {
        echo "Checking {$for}... ";
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

function print_xml_errors()
{
    global $ac;
    $report = $ac['LANG'] == 'en' || $ac['XPOINTER_REPORTING'] == 'yes';
    $output = ( $ac['STDERR_TO_STDOUT'] == 'yes' ) ? STDOUT : STDERR ;

    $errors = libxml_get_errors();
    libxml_clear_errors();

    $filePrefix = "file:///";
    $tempPrefix = realpath( __DIR__ . "/temp" ) . "/";
    $rootPrefix = realpath( __DIR__ . "/.." ) . "/";

    if ( count( $errors ) > 0 )
        fprintf( $output , "\n" );

    foreach( $errors as $error )
    {
        $mssg = rtrim( $error->message );
        $file = $error->file;
        $line = $error->line;
        $clmn = $error->column;

        if ( str_starts_with( $mssg , 'XPointer evaluation failed:' ) && ! $report )
            continue; // Translations can omit these, to focus on fatal errors

        if ( str_starts_with( $file , $filePrefix ) )
            $file = substr( $file , strlen( $filePrefix ) );
        if ( str_starts_with( $file , $tempPrefix ) )
            $file = substr( $file , strlen( $tempPrefix ) );
        if ( str_starts_with( $file , $rootPrefix ) )
            $file = substr( $file , strlen( $rootPrefix ) );

        $prefix = $error->level === LIBXML_ERR_FATAL ? "FATAL" : "error";

        fwrite( $output , "[$prefix $file {$line}:{$clmn}] {$mssg}\n" );
    }
}

function find_xml_files($path) // {{{
{
    $path = rtrim($path, '/');
    $prefix_len = strlen($path . '/');
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($files as $fileinfo) {
        if ($fileinfo->getExtension() === 'xml') {
            yield substr($fileinfo->getPathname(), $prefix_len);
        }
    }
} // }}}

$srcdir  = dirname(__FILE__);
$workdir = $srcdir;
$basedir = $srcdir;
$rootdir = dirname($basedir);

/**
 * When checking out this repository on GitHub Actions, the workspace  directory is "/home/runner/work/doc-base/doc-base".
 *
 * To avoid applying dirname() here, we check if we are running on GitHub Actions.
 *
 * @see https://docs.github.com/en/free-pro-team@latest/actions/reference/environment-variables#default-environment-variables
 */
if (getenv('GITHUB_ACTIONS') !== 'true' && basename($rootdir) === 'doc-base') {
    $rootdir = dirname($rootdir);
}

// Settings {{{
$cygwin_php_bat = "{$srcdir}/../phpdoc-tools/php.bat";
$php_bin_names = array('php', 'php5', 'cli/php', 'php.exe', 'php5.exe', 'php-cli.exe', 'php-cgi.exe');
// }}}

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
    'PARTIAL' => 'no',
    'DETAILED_ERRORMSG' => 'no',
    'VERSION_FILES'  => 'yes',
    'SOURCES_FILE' => 'yes',
    'HISTORY_FILE' => 'yes',
    'LIBXML_CHECK' => 'yes',
    'USE_BROKEN_TRANSLATION_FILENAME' => 'yes',
    'OUTPUT_FILENAME' => $srcdir . '/.manual.xml',
    'GENERATE' => 'no',
    'STDERR_TO_STDOUT' => 'no',
    'INPUT_FILENAME'   => 'manual.xml',
    'TRANSLATION_ONLY_INCL_BEGIN' => '',
    'TRANSLATION_ONLY_INCL_END' => '',
    'XPOINTER_REPORTING' => 'yes',
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
        $v = strlen($opt) > 2 ? substr($opt, 2) : 'yes';
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

        case 'version-files':
            $ac['VERSION_FILES'] = $v;
            break;

        case 'sources-file':
            $ac['SOURCES_FILE'] = $v;
            break;

        case 'history-file':
            $ac['SOURCES_FILE'] = $v;
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
            break;

        case 'stderr-to-stdout':
            $ac['STDERR_TO_STDOUT'] = $v;
            break;

        case 'xpointer-reporting':
            $ac['XPOINTER_REPORTING'] = $v;
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
file_put_contents( __DIR__ . "/temp/lang" , $ac['LANG'] );

checking("whether the language is supported");
$LANGDIR = "{$ac['rootdir']}/{$ac['LANG']}";
if (!file_exists($LANGDIR) || !is_readable($LANGDIR)) {
    checkerror("No language directory found.");
}

$ac['LANGDIR'] = basename($LANGDIR);
$ac['EN_DIR'] = 'en';
checkvalue("yes");

checking("for partial build");
checkvalue($ac['PARTIAL']);

checking('whether to enable detailed XML error messages');
checkvalue($ac['DETAILED_ERRORMSG']);

if ($ac["GENERATE"] != "no") {
    $ac["ONLYDIR"] = dirname(realpath($ac["GENERATE"]));
}

git_clean();    // Idempotent clean up
git_status();   // Show local repository status

function git_clean()
{
    $dir = escapeshellarg( __DIR__ );
    $cmd = "git -C $dir clean temp -fdx --quiet";
    $ret = 0;
    passthru( $cmd , $ret );
    if ( $ret != 0 )
    {
        echo "doc-base/temp clean up FAILED.\n";
        exit( 1 );
    }
}

function git_status()
{
    global $ac;

    $repos = array();
    $repos['doc-base']  = $ac['basedir'];
    $repos['en']        = "{$ac['rootdir']}/{$ac['EN_DIR']}";
    $repos[$ac['LANG']] = "{$ac['rootdir']}/{$ac['LANG']}";

    $output = "";
    foreach ( $repos as $name => $path )
    {
        $path = escapeshellarg( $path );
        $branch = trim( shell_exec( "git -C $path rev-parse --abbrev-ref HEAD" ));
        $branch = $branch == "master" ? "" : " (branch $branch)";
        $output .= str_pad( "$name:" , 10 );
        $output .= rtrim( shell_exec( "git -C $path rev-parse HEAD" ) ?? "" . $branch ) . "\n";
        $output .= rtrim( shell_exec( "git -C $path status -s") ?? "" ) . "\n";
    }
    while( str_contains( $output , "\n\n" ) )
        $output = str_replace( "\n\n" , "\n" , $output );
    echo "\n" , trim( $output ) . "\n\n";
}

// DTD entity layer before first XML loading

dtd_conf_entities();
dtd_file_entities();
dtd_text_entities();

function dtd_conf_entities()
{
    global $ac;
    $lang = $ac["LANG"];
    $conf = [];

    $conf[] = "<!ENTITY LANG '$lang'>";

    if ( $lang != 'en' )
    {
        $trans1 = realpain( __DIR__ . "/../$lang/language-defs.ent" );
        $trans2 = realpain( __DIR__ . "/../$lang/language-snippets.ent" );
        $trans3 = realpain( __DIR__ . "/../$lang/extensions.ent" );

        $conf[] = "<!ENTITY % translation-defs       SYSTEM '$trans1'>";
        $conf[] = "<!ENTITY % translation-snippets   SYSTEM '$trans2'>";
        $conf[] = "<!ENTITY % translation-extensions SYSTEM '$trans3'>";
    }

    if ( $ac['CHMENABLED'] == 'yes' )
    {
        $chmpath = realpain( __DIR__ . "/chm/manual.chm.xml" );
        $conf[] = "<!ENTITY manual.chmonly SYSTEM '$chmpath'>";
    }
    else
        $conf[] = "<!ENTITY manual.chmonly ''>";

    file_put_contents( __DIR__ . "/temp/manual.inc" , implode( "\n" , $conf ) );
}

function dtd_file_entities()
{
    global $ac;
    $lang = $ac["LANG"];
    $withphp = $ac['PHP'];
    $withchm = $ac['CHMENABLED'] == 'yes';

    $parts = array();
    $parts[] = $withphp;
    $parts[] = __DIR__ . "/scripts/file-entities.php";
    if ( $lang != "en" )
        $parts[] = $lang;
    if ( $withchm )
        $parts[] = '--chmonly';

    foreach ( $parts as & $part )
        $part = escapeshellarg( $part );
    $cmd = implode( ' ' , $parts );
    $ret = 0;
    passthru( $cmd , $ret );

    if ( $ret != 0 )
    {
        echo "doc-base/scripts/file-entities.php FAILED.\n";
        exit( 1 );
    }
}

function dtd_text_entities()
{
    global $ac;
    $php = $ac['PHP'];
    $lang = $ac["LANG"];

    $parts = array();
    $parts[] = $php;
    $parts[] = __DIR__ . "/scripts/entities.php";
    $parts[] = "en";
    if ( $lang != "en" )
        $parts[] = $lang;

    foreach ( $parts as & $part )
        $part = escapeshellarg( $part );
    $cmd = implode( ' ' , $parts );
    $ret = 0;
    passthru( $cmd , $ret );

    if ( $ret != 0 )
    {
        echo "doc-base/scripts/entities.php FAILED.\n";
        exit( 1 );
    }
}

checking("for if we should generate a simplified file");
if ($ac["GENERATE"] != "no") {
    if (!file_exists($ac["GENERATE"])) {
        checkerror("Can't find {$ac["GENERATE"]}");
    }
    $tmp = realpath($ac["GENERATE"]);
    $ac["GENERATE"] = str_replace($ac["ROOTDIR"].$ac["LANGDIR"], "", $tmp);
    $str = "\n<!ENTITY developer.include.file SYSTEM 'file:///{$ac["GENERATE"]}'>";
    file_put_contents("{$ac["basedir"]}/entities/file-entities.ent", $str, FILE_APPEND);
}
checkvalue($ac["GENERATE"]);

function dom_load( DOMDocument $dom , string $filename , string $baseURI = "" ) : bool
{
    $filename = realpath( $filename );
    $options = LIBXML_NOENT | LIBXML_COMPACT | LIBXML_BIGLINES | LIBXML_PARSEHUGE;
    return $dom->load( $filename , $options );
}

function dom_saveload( DOMDocument $dom , string $filename = "" ) : string
{
    if ( $filename == "" )
        $filename = __DIR__ . "/temp/manual.xml";

    libxml_clear_errors();
    $dom->save( $filename );
    dom_load( $dom , $filename );

    return $filename;
}

echo "Creating monolithic temp/manual.xml... ";
$dom = new DOMDocument();

if ( dom_load( $dom , "{$ac['srcdir']}/{$ac["INPUT_FILENAME"]}" ) )
{
    dom_saveload( $dom ); // correct file/line/column on error messages
    echo " done.\n";
}
else
{
    echo "failed.\n";
    print_xml_errors();
    individual_xml_broken_check();
    errors_are_bad(1);
}

function individual_xml_broken_check()
{
    $cmd = array();
    $cmd[] = $GLOBALS['ac']['PHP'];
    $cmd[] = __DIR__ . "/scripts/broken.php";
    $cmd[] = $GLOBALS['ac']['LANG'];
    foreach ( $cmd as & $part )
        $part = escapeshellarg( $part );
    $ret = 0;
    $cmd = implode( ' ' , $cmd );
    passthru( $cmd , $ret );
    if ( $ret != 0 )
    {
        echo "doc-base/scripts/broken.php FAILED.\n";
        exit( 1 );
    }
}

echo "Expanding XIncludes... ";

$total  = xinclude_run_byid( $dom );
$total += xinclude_run_xpointer( $dom );

if ( $total == 0 )
    echo "failed.\n";
else
    echo "done: $total tags replaced.\n";

xinclude_residual_fixup( $dom );

function xinclude_run_byid( DOMDocument $dom )
{
    // libxml does not implements the XInclude 1.1 feature,
    // so we need to *simulate* its *recursive* nature here.

    $total = 0;
    $xpath = new DOMXPath( $dom );
    $xpath->registerNamespace( "xi" , "http://www.w3.org/2001/XInclude" );

    // Resolve xpointers via a precomputed map; on duplicate xml:ids, first wins.
    // Avoids quadratic tree walks (~ 90% performance gain).
    $byId = [];
    foreach( $xpath->query( "//*[@xml:id]" ) as $node ) {
        $byId[$node->getAttribute("xml:id")] ??= $node;
    }

    for( $run = 0 ; $run < 10 ; $run++ )
    {
        $xincludes = $xpath->query( "//xi:include" );

        $changed = false;
        foreach( $xincludes as $xinclude )
        {
            $xpointer = $xinclude->getAttribute( "xpointer" );
            $target = $byId[ $xpointer ] ?? null;

            if ( $target == null )
                continue;

            $other = new DOMDocument( '1.0' , 'utf8' );
            $frags = $other->createDocumentFragment();
            $other->append( $frags );
            $frags->append( $other->importNode( $target , true ) ); // dup add

            // "attributes in xml: namespace are not copied"

            $oxpth = new DOMXPath( $other );
            $attribs = $oxpth->query( "//@*" );

            foreach( $attribs as $attrib )
                if ( $attrib->prefix == "xml" )
                    $attrib->parentNode->removeAttribute( $attrib->nodeName );

            $insert = $dom->importNode( $frags , true );                // dup
            $xinclude->parentNode->insertBefore( $insert , $xinclude ); // add
            $xinclude->parentNode->removeChild( $xinclude );            // del

            $total++;
            $changed = true;
            libxml_clear_errors();
        }

        if ( ! $changed )
            return $total;
    }
    echo "XInclude nested too deeply (by xml:id). Update `configure.php`.\n";
    errors_are_bad( 1 );
}

function xinclude_run_xpointer( DOMDocument $dom ) : int
{
    // The return of xinclude() cannot be used for counting or stoping, as it
    // sometimes return zero/negative in cases of partial executions

    $total = 0;
    for( $run = 0 ; $run < 10 ; $run++ )
    {
        libxml_clear_errors();

        $was = count( xinclude_residual_list( $dom ) );
        $dom->xinclude();
        $now = count( xinclude_residual_list( $dom ) );

        $total += $was - $now;

        if ( $was === $now )
            return $total;
    }
    echo "XInclude nested too deeply (xpointer). Update `configure.php`.\n";
    errors_are_bad( 1 );
}

function xinclude_residual_fixup( DOMDocument $dom )
{
    // XInclude failures are soft errors on translations, so we replace
    // residual XInclude tags on translations to keep them validating.

    $fixups = 0;
    $hardfail = false;

    dom_saveload( $dom , __DIR__ . "/temp/manual.err" );
    $nodes = xinclude_residual_list( $dom );

    foreach( $nodes as $node )
    {
        $fixup = null;
        $parent = $node->parentNode->nodeName;
        $target = $node->getAttribute("xpointer");
        $alert = "[[[Failed XInclude '$target']]]";

        if ( $fixups === 0 )
            echo "\nFailed XIncludes, manual parts will be missing. Unresolved xpointers:\n";

        echo "- {$target}\n";
        $fixups++;

        switch( $parent )
        {
            case "listitem":
                $fixup = "<para>$alert</para>";
            case "refentry":
                $fixup = "";
                break;
            case "refsect1":
                $fixup = "<title>_</title><simpara>$alert</simpara>"; // https://github.com/php/phd/issues/181
                break;
            case "tbody":
                $fixup = "<row><entry>$alert</entry></row>";
                break;
            case "variablelist":
                $fixup = "<varlistentry><term></term><listitem><simpara>$alert</simpara></listitem></varlistentry>";
                break;
            default:
                echo "  (Unknown parent of failed XInclude: $parent)\n";
                $hardfail = true;
                continue 2;
        }

        if ( $fixup !== null )
        {
            $other = new DOMDocument( '1.0' , 'utf8' );
            $other->loadXML( "<f>$fixup</f>" );
            foreach( $other->documentElement->childNodes as $otherNode )
            {
                $imported = $dom->importNode( $otherNode , true );
                $node->parentNode->insertBefore( $imported , $node );
            }
        }
        $node->parentNode->removeChild( $node );
    }
    unset( $nodes );

    if ( $fixups > 0 )
        echo "Dumped file: temp/manual.err. Inspect residual xi:include tags in this file.\n\n";

    if ( $hardfail )
    {
        echo <<<MSG

If you are seeing this message in a translation, it means that
the XInclude/XPointers failures reported above are so numerous or unknown,
that configure.php cannot fix up the translated manual to a validating state.
Please report any "Unknown parent" messages to the doc-base repository
and focus on fixing all the XInclude/XPointers failures listed above.

MSG;
        exit( 1 ); // stop here, do not let more messages further confuse the matter
    }

    // A real implementation of XInclude 1.1 will never duplicate any xml:id,
    // but we are stuck with libxml's XInclude 1.0, so we need to fix these
    // duplications yourselfs.

    // Crude and ugly fixup ahead, beware!

    // The code below removes any XInclude IDs without warnings, as they are
    // expected to occur, and also remove any duplicated structural IDs while
    // generating warnings, as they should never happen.

    // See docs/structure.md for details.

    $structural = false;
    $list = [];
    $xpath = new DOMXPath( $dom );
    $nodes = $xpath->query( "//*[@xml:id]" );
    foreach( $nodes as $node )
    {
        $id = $node->getAttribute( "xml:id" );
        if ( isset( $list[ $id ] ) )
        {
            if ( ! str_contains( $id , '..' ) )
            {
                echo "  Deleted duplicated structural xml:id: $id\n";
                $structural = true;
            }
            $node->removeAttribute( "xml:id" );
        }
        $list[ $id ] = $id;
    }
    if ( $structural )
    {
        echo "\n  See: https://github.com/php/doc-base/blob/master/docs/structure.md#xmlid-structure";
        echo "\n  And: https://github.com/php/doc-base/tree/master/scripts/translation";
        echo "\n  Use: qaxml-attributes.php and qaxml-entities.php, with and without --urgent.";
        echo "\n\n";
    }

    // Duplicated structural xml:ids are fatal on doc-en

    $fatal = $GLOBALS['ac']['LANG'] == 'en';

    if ( $structural && $fatal )
        errors_are_bad( 1 );
}

function xinclude_residual_list( DOMDocument $dom ) : DOMNodeList
{
    $xpath = new DOMXPath( $dom );
    $xpath->registerNamespace( "xi" , "http://www.w3.org/2001/XInclude" );
    $nodes = $xpath->query( "//xi:include" );

    return $nodes;
}

// Last save/reload before libxml's RelaxNG validation,
// so file positions and errors are reseted.

$idempath = dom_saveload( $dom );       // idempotent path
$dom->save( $ac["OUTPUT_FILENAME"] );   // historical path

if ($ac['PARTIAL'] != '' && $ac['PARTIAL'] != 'no')
{
    echo "Validating partial temp/manual.xml... ";

    $dom->relaxNGValidate(RNG_SCHEMA_FILE); // we don't care if the validation works or not
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

xml_validate( $dom );

function xml_validate( $dom )
{
    // libxml2's RelaxNG validation shows quadratic to cubic behavior in some cases.

    // > As it stands, libxml2's Relax NG validator doesn't seem suitable for production.
    // -- https://gitlab.gnome.org/GNOME/libxml2/-/issues/448

    // Jing is faster, but depends on Java.

    $out = null;
    $ret = null;
    exec( "java -version 2>&1" , $out , $ret );

    if ( $ret == 0 )
        xml_validate_jing();
    else
        xml_validate_libxml( $dom );
}

function xml_validate_jing()
{
    global $srcdir;     // TODO, static typed field on Conf class
    global $idempath;   // TODO, static typed field on Conf class

    echo "Validating temp/manual.xml (jing)... ";

    $out = null;
    $ret = null;
    $schema = RNG_SCHEMA_FILE;
    $cmdJing = "java -jar {$srcdir}/docbook/jing.jar {$schema} {$idempath}";
    exec( $cmdJing , $out , $ret );

    if ( $ret == 0 )
    {
        echo "done.\n";
        return;
    }
    else
    {
        echo "failed.\n";
        if ( is_array( $out ) )
            foreach ( $out as $line )
                echo "$line\n";
        errors_are_bad( 1 );
    }
}

function xml_validate_libxml( $dom )
{
    echo "Validating temp/manual.xml (libxml)... ";

    if ( $dom->relaxNGValidate( RNG_SCHEMA_FILE ) )
    {
        echo "done.\n";
    }
    else
    {
        echo "failed.\n";
        print_xml_errors();
        errors_are_bad( 1 );
    }
}

echo "\nAll good. Saved temp/manual.xml\n";
echo "All you have to do now is run 'phd -d {$idempath}'\n";
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

// All PhD stuff, after XML validation.

phd_acronym();
php_history();
phd_sources();
phd_version();

exit(0); // Finished successfully.



// TODO: Should this moved to github/php/phd?
// Any input/state can be serialized into doc-base/temp/phd-conf.json.

function phd_acronym()
{
}

function php_history()
{
    global $ac;
    if ($ac['HISTORY_FILE'] !== 'yes')
        return;

    echo 'PhD history:';

    $lang_mod_file = (($ac['LANG'] !== 'en') ? ("{$ac['rootdir']}/{$ac['EN_DIR']}") : ("{$ac['rootdir']}/{$ac['LANGDIR']}")) . "/fileModHistory.php";
    $doc_base_mod_file = __DIR__ . "/fileModHistory.php";

    $history_file = null;
    if (file_exists($lang_mod_file)) {
        $history_file = include $lang_mod_file;
        if (is_array($history_file)) {
            echo ' copy,';
            $isFileCopied = copy($lang_mod_file, $doc_base_mod_file);
            echo $isFileCopied ? "" : " failed,";
        } else {
            echo " corrupted file '$lang_mod_file,'";
        }
    } else {
        echo " not found,";
    }

    if (!is_array($history_file)) {
        $history_file = [];
        echo " creating empty,";
        file_put_contents($doc_base_mod_file, "<?php\n\nreturn [];\n");
    }
    echo " done.\n";
}

function phd_sources()
{
    global $ac;
    if ($ac['SOURCES_FILE'] !== 'yes')
        return;

    echo 'PhD sources:';

    echo ' reading,';
    $source_map = array();
    $en_dir = "{$ac['rootdir']}/{$ac['EN_DIR']}";
    $source_langs = array(
        array('base', $ac['srcdir'], array('manual.xml', 'funcindex.xml')),
        array('en', $en_dir, find_xml_files($en_dir)),
    );
    if ($ac['LANG'] !== 'en') {
        $lang_dir = "{$ac['rootdir']}/{$ac['LANGDIR']}";
        $source_langs[] = array($ac['LANG'], $lang_dir, find_xml_files($lang_dir));
    }
    foreach ($source_langs as list($source_lang, $source_dir, $source_files)) {
        foreach ($source_files as $source_path) {
            $source = file_get_contents("{$source_dir}/{$source_path}");
            if (preg_match_all('/ xml:id=(["\'])([^"]+)\1/', $source, $matches)) {
                foreach ($matches[2] as $xml_id) {
                    $source_map[$xml_id] = array(
                        'lang' => $source_lang,
                        'path' => $source_path,
                    );
                }
            }
        }
    }
    asort($source_map);
    echo ' transforming,';
    $dom = new DOMDocument;
    $dom->formatOutput = true;
    $sources_elem = $dom->appendChild($dom->createElement("sources"));
    foreach ($source_map as $id => $source) {
        $el = $dom->createElement('item');
        $el->setAttribute('id', $id);
        $el->setAttribute('lang', $source["lang"]);
        $el->setAttribute('path', $source["path"]);
        $sources_elem->appendChild($el);
    }
    echo " saving,";
    if ($dom->save($ac['srcdir'] . '/sources.xml')) {
        echo " done.\n";
    } else {
        echo " fail!\n";
    }
}

function phd_version()
{
    global $ac;
    if ($ac['VERSION_FILES'] !== 'yes')
        return;

    echo 'PhD version:';

    $dom = new DOMDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput       = true;

    $tmp = new DOMDocument;
    $tmp->preserveWhiteSpace = false;

    $versions = $dom->appendChild($dom->createElement("versions"));

    echo ' reading,';
    if ($ac["GENERATE"] != "no") {
        $globdir = dirname($ac["GENERATE"]) . "/{../../}versions.xml";
    }
    else {
        $globdir = $ac['rootdir'] . '/en';
        $globdir .= "/*/*/versions.xml";
    }
    echo ' transforming,';
    if (!defined('GLOB_BRACE')) {
        define('GLOB_BRACE', 0);
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
    echo ' saving,';

    if ($dom->save($ac['srcdir'] . '/version.xml')) {
        echo " done.\n";
    } else {
        echo " fail!\n";
    }
}
