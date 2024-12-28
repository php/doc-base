<?php /*
+----------------------------------------------------------------------+
| Copyright (c) 1997-2025 The PHP Group                                |
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
ignoring undefined entities. If an directory is informed, the test is
applied in all .xml files in directory and sub directories.

This tool also cares for directories marked with .xmlfragmentdir, so
theses files are tested in relaxed semantics for XML fragments.       */

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );

if ( count( $argv ) < 2 )
    print_usage_exit( $argv[0] );

array_shift( $argv );
foreach( $argv as $arg )
{
    if ( file_exists( $arg ) )
    {
        if ( is_file( $arg ) )
            testFile( $arg );
        if ( is_dir( $arg ) )
            testDir( $arg );
        continue;
    }
    echo "Path does not exist: $arg\n";
}

function print_usage_exit( $cmd )
{
    fwrite( STDERR , "  Wrong paramater count. Usage:\n" );
    fwrite( STDERR , "    {$cmd} path:\n" );
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
    $message = libxml_get_errors()[0]->message;
    $message = str_replace( "ZZZ" , "\f" , $message );
    [ $prefix , $suffix ] = explode( "\f" , $message );
    libxml_clear_errors();

    $doc->loadXML( $outside );
    $extra = libxml_get_errors()[0]->message;
    libxml_clear_errors();
}

function testFile( string $filename , bool $fragment = false )
{
    static $prefix = "", $suffix = "", $extra = "";
    if ( $extra == "" )
        setup( $prefix , $suffix , $extra );

    $doc = new DOMDocument();
    $doc->recover            = true;
    $doc->resolveExternals   = false;
    $doc->substituteEntities = false;
    libxml_use_internal_errors( true );

    $contents = file_get_contents( $filename );
    if ( $fragment )
        $contents = "<f>{$contents}</f>";
    $doc->loadXML( $contents );

    $errors = libxml_get_errors();
    libxml_clear_errors();

    foreach( $errors as $error )
    {
        $message = $error->message;
        if ( str_starts_with( $message , $prefix ) && str_ends_with( $message , $suffix ) )
            continue;

        //if ( $message == $extra ) // Disabled as unnecessary. Also, this indicates that some
        //    continue;             // some entity reference is used at an unusual position.

        $lin = $error->line;
        $col = $error->column;
        echo "Broken XML file:\n";
        echo "  Path:  $filename [$lin,$col]\n";
        echo "  Error: $message\n";
        return;
    }
}

function testDir( string $dir )
{
    $dir = realpain( $dir );
    $files = scandir( $dir );
    $fragment = false;
    $subdirs = [];

    foreach( $files as $file )
    {
        if ( $file == ".xmlfragmentdir" )
        {
            $fragment = true;
            continue;
        }
        if ( $file[0] == "." )
            continue;

        $fullpath = realpain( "$dir/$file" );

        if ( is_dir ( $fullpath ) )
        {
            $subdirs[] = $fullpath;
            continue;
        }

        if ( str_ends_with( $fullpath , ".xml" ) )
            testFile( $fullpath , $fragment );
    }

    foreach( $subdirs as $dir )
        testDir( $dir );
}

function realpain( string $path , bool $touch = false , bool $mkdir = false ) : string
{
    // pain is real

    // care for external XML tools (realpath() everywhere)
    // care for Windows builds (foward slashes everywhere)
    // avoid `cd` and chdir() like the plague

    $path = str_replace( "\\" , '/' , $path );

    if ( $mkdir && ! file_exists( $path ) )
        mkdir( $path , recursive: true );

    if ( $touch && ! file_exists( $path ) )
        touch( $path );

    $res = realpath( $path );
    if ( is_string( $res ) )
        $path = $res;

    return $path;
}
