<?php /*
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
| Authors:     André L F S Bacci <ae php.net>                          |
+----------------------------------------------------------------------+

# Description

This command line utility test if an file is valid standalone XML file,
accepting undefined entities references. If an directory is informed,
the test is applied in all .xml files in directory and sub directories.

This tool also cares for directories marked with .xmlfragmentdir, so
theses files are tested in relaxed semantics for XML Fragments.       */

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );

if ( count( $argv ) < 2 )
    print_usage_exit( $argv[0] );
array_shift( $argv );

$dos2unix = false;
foreach( $argv as & $arg )
{
    if ( $arg == "--dos2unix" )
    {
        $dos2unix = true;
        $arg = null;
    }
    else
        $arg = str_replace( '\\' , '/' , $arg );
}
$paths = array_filter( $argv );
if ( count( $paths) == 0 )
    print_usage_exit();

foreach( $paths as $path )
{
    $dnt = true;
    if ( $path == 'en' || str_ends_with( $path , '/en' ) )
        $dnt = false;

    if ( file_exists( $path ) )
    {
        if ( is_file( $path ) )
            testFile( $path , $dnt );
        if ( is_dir( $path ) )
            testDir( $path , $dnt );
        continue;
    }
    echo "Path does not exist: $path\n";
}

function print_usage_exit( $cmd )
{
    fwrite( STDERR , "  Wrong paramater count. Usage:\n" );
    fwrite( STDERR , "    {$cmd} [--dos2unix] path\n" );
    exit;
}

function setup( string & $prefix , string & $suffix , string & $extra )
{
    // Undefined entities generate TWO different error messages on libxml
    // - "Entity '?' not defined" (for entity inside elements)
    // - "Extra content at the end of the document" (entity outside elements)

    $inside = "<x>&ZZZ;</x>";
    $outside = "<x/>&ZZZ;";

    $doc = new DOMDocument();
    $doc->recover            = true;
    $doc->resolveExternals   = false;
    $doc->substituteEntities = false;
    libxml_use_internal_errors( true );

    $doc->loadXML( $inside );
    $message = trim( libxml_get_errors()[0]->message );
    $message = str_replace( "ZZZ" , "\f" , $message );
    [ $prefix , $suffix ] = explode( "\f" , $message );
    libxml_clear_errors();

    $doc->loadXML( $outside );
    $extra = trim( libxml_get_errors()[0]->message );
    libxml_clear_errors();
}

function testFile( string $filename , bool $checkDnt , bool $fragmentDir = false )
{
    $contents = file_get_contents( $filename );

    if ( str_starts_with( $contents , b"\xEF\xBB\xBF" ) )
    {
        echo "Wrong XML file:\n";
        echo "  Issue: XML file with BOM. Several tools may misbehave.\n";
        echo "  Path:  $filename\n";
        echo "  Hint:  You can try auto fix this with 'doc-base/scripts/broken.php --dos2unix langdir'.\n";
        echo "\n";
        autofix_dos2unix( $filename );
    }

    if ( PHP_EOL == "\n" && str_contains( $contents , "\r") )
    {
        echo "Wrong XML file:\n";
        echo "  Issue: XML file contains \\r. Several tools may misbehave.\n";
        echo "  Path:  $filename\n";
        echo "  Hint:  You can try auto fix this with 'doc-base/scripts/broken.php --dos2unix langdir'.\n";
        echo "\n";
        autofix_dos2unix( $filename );
    }

    if ( $checkDnt && strpos( $contents , '<?do-not-translate?>' ) !== false )
    {
        echo "File marked do-not-translate in translation:\n";
        echo "  Issue: Manual build may fail.\n";
        echo "  Path:  $filename\n";
        echo "\n";
        autofix_dos2unix( $filename );
    }

    static $prefix = "", $suffix = "", $extra = "";
    if ( $extra == "" )
        setup( $prefix , $suffix , $extra );

    $doc = new DOMDocument();
    $doc->recover            = true;
    $doc->resolveExternals   = false;
    $doc->substituteEntities = false;
    libxml_use_internal_errors( true );

    if ( $fragmentDir )
        $contents = "<f>{$contents}</f>";
    $doc->loadXML( $contents );

    $errors = libxml_get_errors();
    libxml_clear_errors();

    foreach( $errors as $error )
    {
        $message = trim( $error->message );
        $hintFragDir = false;

        if ( str_starts_with( $message , $prefix ) && str_ends_with( $message , $suffix ) )
            continue;
        //if ( $message == $extra ) // Disabled as unnecessary. Also, this indicates that some
        //    continue;             // some entity reference is used at an unusual position.
        if ( $message == $extra )
            $hintFragDir = true;

        $lin = $error->line;
        $col = $error->column;
        echo "Broken XML file:\n";
        echo "  Issue: $message\n";
        echo "  Path:  $filename [$lin,$col]\n";
        if ( $hintFragDir )
            echo "  Hint:  Copy the .xmlfragmentdir file, or check for misplaced entity references (outside any enclosing tag).\n";
        echo "\n";
        return;
    }
}

function testDir( string $dir , bool $checkDnt )
{
    $dir = realpath( $dir );
    $files = scandir( $dir );
    $fragmentDir = false;
    $subdirs = [];

    foreach( $files as $file )
    {
        if ( $file == ".xmlfragmentdir" )
        {
            $fragmentDir = true;
            continue;
        }
        if ( $file[0] == "." )
            continue;

        $fullpath = realpath( "$dir/$file" );

        if ( is_dir ( $fullpath ) )
        {
            $subdirs[] = $fullpath;
            continue;
        }

        if ( str_ends_with( $fullpath , ".xml" ) )
            testFile( $fullpath , $checkDnt , $fragmentDir );
    }

    foreach( $subdirs as $dir )
        testDir( $dir , $checkDnt );
}

function autofix_dos2unix( string $filename )
{
    if ( $GLOBALS['dos2unix'] )
    {
        $cmd = "dos2unix -r " . escapeshellarg( $filename );
        system( $cmd );
        echo "\n";
    }
}
