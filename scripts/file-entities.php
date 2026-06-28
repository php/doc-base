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

This script creates various "file entities" files, that is, DTD entities
that include files directly, and some "dir entities", that includes all
XML files from a directory. The historical naming schema is:

- dir.dir.file         : includes one file from dir/dir/file.xml
- dir.dir.entities.dir : includes all files from dir/dir/dir/*.xml

The files are created at:

- doc-base/temp/file-entites.ent
- doc-base/temp/file-entites/dir.dir.ent

The file entity for directories (file listings) are keep as individual
files, to avoid these libxml errors, in some OS/versions:

- Detected an entity reference loop [1]
- Maximum entity amplification factor exceeded [2]

See LIBXML_LIMITS_HACK below. This workaround creates about a thousand
files per running, that slows down even more the building of the manual
on HDD systems.

There is also a mysterious replacement of underlines for dashes on entity
names. In future, would be better to remove this, so manual writing gets
less surprising.

[1] https://github.com/php/doc-base/pull/183
[2] https://github.com/php/doc-en/pull/4330

*/

const ENTITY_NAME_MINUS = true;
const ENTITY_NAME_EQUAL = false;
const LIBXML_LIMITS_HACK = true;

// Setup

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );
set_time_limit( 0 );
ob_implicit_flush();

// Usage

$lang = "";
$langs = [ "en" ];
$langBase = realpain( __DIR__ . "/../.." );
$chmonly = false;

array_shift( $argv );
foreach( $argv as $arg )
{
    $lang = rtrim( $arg , "\\/" );
    $langs[] = $lang;
}

// Generation

print "Running file-entities.php... ";

$allFiles = [];
$entities = [];

foreach( $langs as $lang )
    load_all_files( $langBase , $lang , $allFiles );
check_case_conflict( $allFiles );
generate_entities( $allFiles , $entities );

// old scheme
//  file en
//  list en
//  file? lang

// Fixups

pushEntity( "global.function-index", realpain( __DIR__ . "/../funcindex.xml" ) , $entities ); // TODO move this file from doc-bese to doc-en, with a <?do-not-translate tag

// Output

writeEntities( $entities );
$total = count( $entities );
print "done: $total entities.\n";

exit( 0 );

function load_all_files( string $langBase , string $lang , array & $allFIles )
{
    $todo = [ "" ];
    while ( count( $todo ) > 0 )
    {
        $dir = array_pop( $todo );
        $scan = "$langBase/$lang/$dir";
        $paths = scandir( $scan );
        foreach( $paths as $path )
        {
            if ( $path == "" || $path[0] == '.' )
                continue;

            $part = trim( "$dir/$path" , '/' );
            $full = "$langBase/$lang/$dir/$path";
            if ( is_dir( $full ) )
            {
                $todo[] = $part;
                continue;
            }
            if ( ! str_ends_with( $part , ".xml" ) )
                continue;

            $real = realpain( $full );
            $allFIles[ $part ] = $real;
        }
    }
}

function check_case_conflict( array $allFIles )
{
    $mixedCase = [];
    foreach( $allFIles as $name => $file )
    {
        $lname = strtolower( $name );
        if ( isset( $mixedCase[ $lname ] ) && $mixedCase[ $lname ] != $name )
        {
            print <<<END
            \n\n
            BRICKED/BROKEN BUILD on case insensitive file systems!

            Detected file entities names, distinct only by upper/lower case:
            - {$mixedCase[ $lname ]}
            - $name

            This will PERMANENTLY BRICK manual build on Windows machines!

            If you are seeing this message building doc-en, avoid committing any changes
            on repository, and if it's already committed, revert and send a heads up on
            mail lists, on how to fix the issue (refer to this message).

            If you are seeing this message building a translation, this means that the
            translation has files or directories that differ from doc-en only by
            upper or lower case letters. Find these differences and fix them at the git
            level ('git mv"). After, delete the files and 'git restore' them, to see if
            the 'git mv' worked.

            This message only may be visible on non-Windows machines. Mixed cases inside
            a repository, or between repositories, WILL cause difficult to debug build
            failures on Windows, without any other information. After a local checkout
            is bricked, there is no easy fix, other than DELETING the local checkout and
            doing a fresh checkout.

            See: https://github.com/php/doc-en/pull/4330#issuecomment-2557306828\n\n
            END;
            exit( 1 );
        }
        $mixedCase[ $lname ] = $name;
    }
}

function generate_entities( array $allFiles , array & $entities )
{
    // Direct file inclusion is easy. It is just a
    // DTD entity that points to a filename, without
    // the extension

    foreach( $allFiles as $name => $file )
    {
        $name = substr( $name , 0 , -4 );
        $name = normalizeEntityName( $name );
        $text = "<!ENTITY $name SYSTEM '$file'>";
        pushEntity( $name , $text , $entities );
    }

    // Inclusion of reference/ directories is a little more involved.
    // From the entity name of the file, is calculated  list name and
    // one list item. The list items are then grouped, and a virtual
    // DTD entity for the directory is created with these components.

    // Note that these "list" entities do not contain the final
    // filenames, as there is only a SYSTEM attribute per DTD entity.
    // The contents of list entities are the concatenated list of
    // entity references of the final files.

    $mapNameList = [];

    foreach( $allFiles as $name => $file )
    {
        // Only generate directory inclusions for reference/

        if ( ! str_starts_with ( $name , 'reference' ) )
            continue;

        // List name
        //
        // Discard the file part, and reform the name from
        //   dir.dir.dir
        // to
        //   dir.dir.entities.dir

        $parts = explode( '/' , $name );
        array_pop( $parts );
        $last = array_pop( $parts );
        $parts[] = 'entities';
        $parts[] = $last;
        $listName = implode( '.' , $parts );

        // List item

        $listItem = "&{$name};";

        // Collect

        iF ( ! isset( $mapNameList[$listName] ) )
            $mapNameList[$listName] = [];
        $mapNameList[$listName][] = $listItem;
    }

    // List emit

    foreach( $mapNameList as $name => $list )
    {
        sort( $list );
        $text = implode ( "\n" , $list );
        pushEntity( $name , $text , $entities );
    }
}

function normalizeEntityName( string $name ) : string
{
    $name = str_replace( '\\' , '/' , $name );
    $name = str_replace( '/' , '.' , $name );
    $name = trim( $name , '.' );
    return $name;
}

function pushEntity( string $name , string $text , array & $entities )
{
$debug = false;
if ( str_contains( $name , "apache" ) )
    $debug = true;

    if ( $name == "" || $text == "" )
    {
        print "Something went very wrong on file-entities.php.\n";
        exit( 1 );
    }

    $nameEqual = normalizeEntityName( $name );                // Almost same as on disk
    $nameMinus = str_replace( '_' , '-' , $nameEqual ); // Historical behaviour

    if ( $nameEqual == $nameMinus )
    {
        $entities[ $nameEqual ] = $text;
        return;
    }

    // This is the historical behaviour. Why?

    if ( ENTITY_NAME_MINUS )
        $entities[ $nameMinus ] = $text;

    // To make manual writing easier, as file entity names
    // always match file system names.

    if ( ENTITY_NAME_EQUAL )
        $entities[ $nameEqual ] = $text;

    // TODO for the far future
    // - Replace all MINUS entities from doc en
    // - Add the MINUS entities on doc-en/entities/remove.ent
    // - Remove all codepaths related to MINUS constant
}

function writeEntities( array $entities )
{
    ksort( $entities );

    // Output a single temp/file-entities.ent file for direct file inclusion.
    // Output individual files for indirect file inclusions on
    //   temp/file-entities/dir.dir.entities.dir.ent
    // See LIBXML_LIMITS_HACK below.

    $outFile = realpain(  __DIR__ . "/../temp/file-entities.ent" , touch: true );
    $lstFile = realpain(  __DIR__ . "/../temp/file-entities.txt" , touch: true );
    $sepPath = realpain(  __DIR__ . "/../temp/file-entities" , mkdir: true );

    $file = fopen( $outFile , "w" );
    if ( ! $file )
    {
        print "Failed to open $outFile\n.";
        exit( 1 );
    }
    fputs( $file , "<!-- DON'T TOUCH - AUTOGENERATED BY file-entities.php -->\n\n" );

    // Life could be simpler, but the building of PHP Manual is already
    // triping some hardcoded limits of bundled libxml2.

    // Off loading file entities that expand to more file entities,
    // as external files, somehow avoid these limits.

    if ( LIBXML_LIMITS_HACK )
    {
        foreach ( $entities as $name => $text )
            if ( $text[0] == '&' )
                writeEntityIndirectSlow( $file , $name , $text , $sepPath );
            else
                fputs( $file , "$text\n" );
    }
    else
    {
        foreach ( $entities as $name => $text )
            fputs( $file , "$text\n" );
    }

    fclose( $file );

    // After everything is said and done, also output a listing file, so
    // it is possible to analyse collisions between 'text' and 'file'
    // entities.

    $contents = implode( "\n" , array_keys( $entities ) );
    file_put_contents( $lstFile , $contents );
}

function writeEntityIndirectSlow( $file , string $name , string $text , string $baseDir )
{
    $newFilename = "{$baseDir}/{$name}.ent";

    // The entity will point to to a new, individual filename

    fputs( $file , "<!ENTITY $name SYSTEM '$newFilename'>\n" );

    // And the new individual file will hold the final text

    file_put_contents( $newFilename , $text );
}

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
