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
| Authors:    Hartmut Holzgraefe <hholzgra@php.net>                    |
|             Gabor Hojtsy <goba@php.net>                              |
|             André L F S Bacci <ae@php.net>                           |
+----------------------------------------------------------------------+

# Description

This script creates various "file entities", that is, DTD entities that
point to files and file listings, named and composed of:

- dir.dir.file : pulls in a dir/dir/file.xml
- dir.dif.entities.dir : pulls in all files of dir/dir/dir/*.xml

In the original file-entities.php.in, the files are created at:

- doc-base/entities/file-entities.ent
- doc-en/reference/entities.*.xml

In new idempotent mode, files are created at:

- doc-base/temp/file-entites.ent
- doc-base/temp/file-entites.dir.dir.ent

*/

// Setup

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );
set_time_limit( 0 );
ob_implicit_flush();

// Usage

$root = realpain( __DIR__ . "/../.." );
$lang = "";
$chmonly = false;
$debug = false;

array_shift( $argv );
foreach( $argv as $arg )
{
    if ( $arg == "--chmonly" )
    {
        $chmonly = true;
        continue;
    }
    if ( $arg == "--debug" )
    {
        $debug = true;
        continue;
    }
    $lang = $arg;
}

// Main

echo "Creating file-entities.ent... ";

$entities = [];
$mixedCase = [];

generate_file_entities( $root , "en" );
generate_list_entities( $root , "en" );

if ( $lang != "" )
    generate_file_entities( $root , $lang );

pushEntity( "global.function-index", path: realpain( __DIR__ . "/.." ) . "/funcindex.xml" );

if ( ! $chmonly )
    foreach( $entities as $ent )
        if ( str_starts_with( $ent->name , "chmonly." ) )
            $ent->path = '';

$outfile = realpain(  __DIR__ . "/../temp/file-entities.ent" , touch: true );

$file = fopen( $outfile , "w" );
if ( ! $file )
{
    echo "Failed to open $outfile\n.";
    exit( 1 );
}

fputs( $file , "<!-- DON'T TOUCH - AUTOGENERATED BY file-entities.php -->\n\n" );

ksort( $entities );

foreach ( $entities as $ent )
    writeEntity( $file , $ent );

fclose( $file );
echo "done\n";
exit( 0 );



class Entity
{
    function __construct( public string $name , public string $text , public string $path ) {}
}

function pushEntity( string $name , string $text = '' , string $path = '' )
{
    global $entities;
    global $mixedCase;

    $name = str_replace( '_' , '-' , $name );
    $path = str_replace( '\\' , '/' , $path );
    $ent = new Entity( $name , $text , $path );
    $entities[ $name ] = $ent;

    if ( ( $text == "" && $path == "" ) || ( $text != "" && $path != "" ) )
    {
        echo "Something went wrong on file-entities.php.\n";
        exit( 1 );
    }

    $lname = strtolower( $name );
    if ( isset( $mixedCase[ $lname ] ) && $mixedCase[ $lname ] != $name )
    {
        echo "\n\n";
        echo "BROKEN BUILD on case insensitive file systems!\n";
        echo "Detected distinct file entities only by case:\n";
        echo " - {$mixedCase[ $lname ]}\n";
        echo " - $name \n";
        echo "This will PERMANENTLY BRICK manual build on Windows machines!\n";
        echo "Avoid committing this on repository, and if it's already committed,\n";
        echo "revert and send a heads up on mailinst how to fix the issue.\n\n";
        echo "See https://github.com/php/doc-en/pull/4330#issuecomment-2557306828";
        echo "\n\n";
        exit( 1 );
    }
    $mixedCase[ $lname ] = $name;
}

function generate_file_entities( string $root , string $lang )
{
    $path = "$root/$lang";
    $test = realpain( $path );
    if ( $test === false || is_dir( $path ) == false )
    {
        echo "Language directory not found: $path\n.";
        exit( 1 );
    }
    $path = $test;

    file_entities_recurse( $path , array() );
}

function file_entities_recurse( string $langroot , array $dirs )
{
    $dir = rtrim( "$langroot/" . implode( '/' , $dirs ) , "/" );
    $files = scandir( $dir );
    $subdirs = [];

    foreach( $files as $file )
    {
        if ( $file == "" )
            continue;
        if ( $file[0] == "." )
            continue;
        if ( $file == "entities" && count( $dirs ) == 0 )
            continue;

        $path = "$dir/$file";

        if ( is_dir ( $path ) )
        {
            $subdirs[] = $file;
            continue;
        }
        if ( str_ends_with( $file , ".xml" ) )
        {
            $name = implode( '.' , $dirs ) . "." . basename( $file , ".xml" );
            $name = trim( $name , "." );
            pushEntity( $name , path: $path );
        }
    }

    foreach( $subdirs as $subdir )
    {
        $recurse = $dirs;
        $recurse[] = $subdir;
        file_entities_recurse( $langroot , $recurse );
    }
}

function generate_list_entities( string $root , string $lang )
{
    $path = "$root/$lang";
    $test = realpain( $path );
    if ( $test === false || is_dir( $path ) == false )
    {
        echo "Language directory not found: $path\n.";
        exit( 1 );
    }
    $path = $test;

    $dirs = [ "reference" ];
    list_entities_recurse( $path , $dirs );
}

function list_entities_recurse( string $root , array $dirs )
{
    $list = [];

    $dir = rtrim( "$root/" . implode( '/' , $dirs ) , "/" );
    $files = scandir( $dir );
    $subdirs = [];

    foreach( $files as $file )
    {
        if ( $file == "" )
            continue;
        if ( $file[0] == "." )
            continue;

        $path = "$dir/$file";

        if ( is_dir ( $path ) )
        {
            $subdirs[] = $file;
            continue;
        }

        if ( str_ends_with( $file , ".xml" ) )
        {
            $name = implode( '.' , $dirs ) . "." . basename( $file , ".xml" );
            $name = trim( $name , "." );
            $name = str_replace( '_' , '-' , $name );
            $list[ $name ] = "&{$name};";
        }
    }
    ksort( $list );

    $copy = $dirs;
    $last = array_pop( $copy );
    $copy[] = "entities";
    $copy[] = $last;

    $name = implode( "." , $copy );
    $text = implode( "\n" , $list );

    if ( $text != "" )
        pushEntity( $name , text: $text );

//  Old style, pre LIBXML_PARSEHUGE, "directory" entity as external file
//
//        $path = __DIR__ . "/../temp/file-entities." . implode( '.' , $dirs ) . ".ent";
//        file_put_contents( $path , $text );
//        $path = realpain( $path );
//        pushEntity( $name , path: $path );
//

    foreach( $subdirs as $subdir )
    {
        $recurse = $dirs;
        $recurse[] = $subdir;
        list_entities_recurse( $root , $recurse );
    }
}

function writeEntity( $file , Entity $ent )
{
    $name = $ent->name;
    $text = $ent->text;
    $path = $ent->path;

    if ( $path == "" )
        $line = "<!ENTITY $name '$text'>\n";
    else
        $line = "<!ENTITY $name SYSTEM '$path'>\n";

    fwrite( $file , $line );
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
        $path = str_replace( "\\" , '/' , $res );

    return $path;
}
