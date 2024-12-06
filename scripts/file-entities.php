<?php /*
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
| Authors:    Hartmut Holzgraefe <hholzgra@php.net>                    |
|             Gabor Hojtsy <goba@php.net>                              |
|             André L F S Bacci <ae@php.net>                           |
+----------------------------------------------------------------------+

# Description

This script creates various "file entities", that is, antities and files
that define DTD <!ENTITY name SYSTEM path>, named and composed of:

- dir.dir.file : pulls in a dir/dir/file.xml file
- dir.dif.entities.dir : pulls in a entity list for dir/dir/dir/*.xml

In the original file-entities.php.in, the files are created at:

- doc-base/entities/file-entities.ent
- doc-en/reference/entities.*.xml

In new idempotent mode, files are created at:

- doc-base/temp/file-entites.ent
- doc-base/temp/file-entites.dir.dir.ent

# TODO

- Leave it running in idempotent mode for a few months, before erasing
  the const BACKPORT, that exists only to ease debugging the old style
  build.

- Istead of creating ~thousand doc-base/temp/file-entites.*.ent files,
  output an XML bundled file (per github.com/php/doc-base/pull/183)
  so it would be possible to detect accidental overwriting of structural
  entities. The file contents moved to/as <!ENTITY> text.
  PS: This will NOT work, and also will break ALL manuals, see
  comments on PR 183 mentioned above.

*/

const BACKPORT = false; // TODO remove, see above.

// Setup

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );
set_time_limit( 0 );

// Usage

$root = realpath( __DIR__ . "/../.." );
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

$entities = []; // See pushEntity()

generate_file_entities( $root , "en" );
generate_list_entities( $root , "en" );

if ( $lang != "" )
    generate_file_entities( $root , $lang );

pushEntity( "global.function-index", path: realpath( __DIR__ . "/.." ) . "/funcindex.xml" );

if ( ! $chmonly )
    foreach( $entities as $ent )
        if ( str_starts_with( $ent->name , "chmonly." ) )
            $ent->path = '';

$outfile = __DIR__ . "/../temp/file-entities.ent";
touch( $outfile );
$outfile = realpath( $outfile );

$file = fopen( $outfile , "w" );
if ( ! $file )
{
    echo "Failed to open $outfile\n.";
    die(-1);
}

fputs( $file , "<!-- DON'T TOUCH - AUTOGENERATED BY file-entities.php -->\n\n" );

if ( BACKPORT )
    fputs( $file , "\n" );

if ( BACKPORT )
    asort( $entities );
else
    ksort( $entities );

foreach ( $entities as $ent )
    writeEntity( $file , $ent );

fclose( $file );
echo "done\n";
exit;



class Entity
{
    function __construct( public string $name , public string $text , public string $path ) {}
}

function pushEntity( string $name , string $text = '' , string $path = '' )
{
    global $entities;

    $name = str_replace( '_' , '-' , $name );
    $ent = new Entity( $name , $text , $path );
    $entities[ $name ] = $ent;

    if ( ( $text == "" && $path == "" ) || ( $text != "" && $path != "" ) )
    {
        echo "Something went wrong on file-entities.php.\n";
        exit(-1);
    }
}

function generate_file_entities( string $root , string $lang )
{
    $path = "$root/$lang";
    $test = realpath( $path );
    if ( $test === false || is_dir( $path ) == false )
    {
        echo "Language directory not found: $path\n.";
        exit(-1);
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
    $test = realpath( $path );
    if ( $test === false || is_dir( $path ) == false )
    {
        echo "Language directory not found: $path\n.";
        exit(-1);
    }
    $path = $test;

    if ( BACKPORT ) // Spurious file generated outside reference/
        pushEntity( "language.predefined.entities.weakreference", path: "$root/$lang/language/predefined/entities.weakreference.xml" );

    $dirs = array( "reference" );
    list_entities_recurse( $path , $dirs );
}

function list_entities_recurse( string $root , array $dirs )
{
    $list = array();

    $dir = rtrim( "$root/" . implode( '/' , $dirs ) , "/" );
    $files = scandir( $dir );
    $subdirs = [];

    foreach( $files as $file )
    {
        if ( $file == "" )
            continue;
        if ( $file[0] == "." )
            continue;
        if ( BACKPORT && str_starts_with( $file , "entities.") )
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

    // The entity file names collected on
    //
    //   doc-lang/reference/apache/functions
    //
    // generate an entity named
    //
    //   reference.apache.ENTITIES.functions
    //
    // that is saved on parent directory, with filename
    //
    //   doc-lang/reference/apache/ENTITIES.functions.xml
    //
    // new style has the files saved as
    //
    //   doc-base/temp/file-entities.reference.apache.functions.ent
    //
    // and in a far future, may only outputs: (see doc-base PR 183)
    //
    //   doc-base/temp/file-entities.xml

    $copy = $dirs;
    $last = array_pop( $copy );
    $copy[] = "entities";
    $copy[] = $last;

    $name = implode( "." , $copy );

    if ( BACKPORT )
        $path = "$dir/../entities.$last.xml";
    else
        $path = __DIR__ . "/../temp/file-entities." . implode( '.' , $dirs ) . ".ent";

    $contents = implode( "\n" , $list );
    if ( $contents != "" )
    {
        file_put_contents( $path , $contents );
        $path = realpath( $path );
        pushEntity( $name , path: $path );
    }

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

    if ( BACKPORT )
    {
        if ( $path == "" )
            $line = sprintf("<!ENTITY %-40s        ''>\n" , $name ); // was on original, possibly unused
        else
            $line = sprintf("<!ENTITY %-40s SYSTEM 'file:///%s'>\n" , $name , $path );
    }
    else
    {
        if ( $path == "" )
            $line = "<!ENTITY $name '$text'>\n";
        else
            $line = "<!ENTITY $name SYSTEM '$path'>\n";
    }

    fwrite( $file , $line );
}
