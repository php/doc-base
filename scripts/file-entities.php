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

const BACKPORT_MIXED_REPLACE = true;
const ENTITY_NAME_REPLACE = true;
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
    scan_files( $langBase , $lang , $allFiles );
check_case_conflict( $allFiles );

generate_entities( $allFiles , $entities );
writeEntities( $entities );

$total = count( $entities );
print "done: $total entities.\n";

exit( 0 );

// old scheme
//  file en
//  list en
//  file? lang

class Entity
{
    public function __construct(
        public string $name,
        public string $text,
        public string $file,
    ) {}
}

function scan_files( string $langBase , string $lang , array & $allFIles )
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
    // Ugly, but necessary
    // TODO move this file from doc-bese to doc-en, with a do-not-translate PI

    $name = 'global.function-index';
    $file = realpain( __DIR__ . "/../funcindex.xml" );
    $text = "<!ENTITY $name SYSTEM '$file'>";
    pushEntity( $entities , $name , $text );

    // Inclusion of a single file is easy. The entity name is the
    // relative path without the .xml extension (sadly), and the text
    // is complete DTD entity with a SYSTEM pointing to the real path
    // of the included file.

    foreach( $allFiles as $path => $file )
    {
        $name = pathToEntityName( $path );
        $text = "<!ENTITY $name SYSTEM '$file'>";
        pushEntity( $entities , $name , $text );
    }

    // Inclusion of reference/ directories is a little more involved.
    // The entity name is calculated from the relative path, but with
    // an 'entities' component added in penultimae position. The
    // contents are concatened DTD entities references, as above.

    // LIBXML_LIMITS_HACK - Unfortunatlly, we nedd to put these entities
    // that expand in another DTD entities as separate files, to bypass
    // some hardcoded limits of libxml2. This is slow, more so on HDDs.

    // BACKPORT_MIXED_REPLACE - Anoying enought, the previous script
    // normalized the entity name, but not the file name of the extra file
    // file. So indirect file entities ends having a surprising convention:
    //
    // <!ENTITY name-dir SYSTEM 'name_dir.ent'>
    //
    // Mind the distinction between _ and - above. In the future, let's
    // remove this, to make debugging easier.

    $groupFilename = []; // LIBXML_LIMITS_HACK
    $groupContents = [];

    foreach( $allFiles as $path => $null )
    {
        // Only generate directory inclusions for reference/

        if ( ! str_starts_with ( $path , 'reference' ) )
            continue;

        // Entity name
        //
        // Discard the file part, 'entities' in the
        // second-to-last position.
        //
        // dir/dir/dir/file.xml -> dir.dir.entities.dir

        $parts = explode( '/' , $path );
        array_pop( $parts );
        $last = array_pop( $parts );
        $parts[] = 'entities';
        $parts[] = $last;
        $entName = implode( '.' , $parts );
        $entName = str_replace( '_' , '-' , $entName ); // BACKPORT_MIXED_REPLACE

        // Entity fila
        //
        // dir/dir/dir/file.xml -> dir.dir.dir.ent

        $parts = explode( '/' , $path );
        array_pop( $parts );
        array_push( $parts , 'ent');
        $entFile = implode( '.' , $parts );

        $groupFilename[ $entName ] = $entFile;

        // Contents

        $name = pathToEntityName( $path );
        $entRef = "&{$name};";

        $groupContents[ $entName ][ $name ] = $entRef;
    }

    // Merge

    foreach( $groupContents as $name => $list )
    {
        ksort( $list );
        $text = implode ( "\n" , $list );
        $file = $groupFilename[ $name ];
        pushEntity( $entities , $name , $text , $file );
    }
}

function pathToEntityName( string $name , string $removeSuffix = "" ) : string
{
    if ( str_ends_with( $name , ".xml" ) )
        $name = substr( $name , 0 , -4 );
    else
        throw new Exception( "Expected extension .xml" );

    $name = str_replace( '\\' , '/' , $name );
    $name = str_replace( '_' , '-' , $name );   // ENTITY_NAME_REPLACE
    $name = str_replace( '/' , '.' , $name );
    $name = trim( $name , '.' );
    return $name;

    // ENTITY_NAME_REPLACE, or a TODO to a far future
    // - Replace all name replaced entities from doc en
    // - Add the removed entities on doc-en/entities/remove.ent
    // - Remove all codepaths related to ENTITY_NAME_REPLACE constant
}

function pushEntity( array & $entities , string $name , string $text , string $file = "" )
{
    if ( $name == "" || $text == "" )
    {
        print "Something went very wrong on file-entities.php.\n";
        exit( 1 );
    }

    $entity = new Entity( $name , $text , $file );
    $entities[ $name ] = $entity;
}

function writeEntities( array $entities )
{
    // Output a single temp/file-entities.ent file for single file inclusion.

    // Output separate files for file list inclusions, at
    //   temp/file-entities/dir.dir.dir.ent
    // LIBXML_LIMITS_HACK

    ksort( $entities );

    $outFile = realpain(  __DIR__ . "/../temp/file-entities.ent" , touch: true );
    $lstFile = realpain(  __DIR__ . "/../temp/file-entities.txt" , touch: true );
    $sepPath = realpain(  __DIR__ . "/../temp/file-entities" , mkdir: true );

    $singleFile = fopen( $outFile , "w" );
    if ( ! $singleFile )
    {
        print "Failed to open $outFile\n.";
        exit( 1 );
    }
    fputs( $singleFile , "<!-- DON'T TOUCH - AUTOGENERATED BY file-entities.php -->\n\n" );

    // Life could be simpler, but the building of PHP Manual is already
    // triping some hardcoded limits of bundled libxml2.

    // Off loading DTD entities that expand to more DTD entities,
    // as external files, somehow avoid these limits.

    if ( LIBXML_LIMITS_HACK )
    {
        foreach ( $entities as $entity )
        {
            $name = $entity->name;
            $text = $entity->text;
            $file = $entity->file;
            $extraFile = "{$sepPath}/{$file}";

            if ( $text[0] == '&' )
                writeEntityIndirectSlow( $singleFile , $extraFile , $name , $text );
            else
                fputs( $singleFile , "$text\n" );
        }
    }
    else
    {
        foreach ( $entities as $name => $text )
            fputs( $singleFile , "$text\n" );
    }

    fclose( $singleFile );

    // After everything is said and done, also output a listing file, so
    // it is possible to analyse collisions between 'text' and 'file'
    // entities.

    $contents = implode( "\n" , array_keys( $entities ) );
    file_put_contents( $lstFile , $contents );
}

function writeEntityIndirectSlow( $singleFile , string $extraFile , string $name , string $text )
{
    // The entity will point to to a new, individual filename

    fputs( $singleFile , "<!ENTITY $name SYSTEM '$extraFile'>\n" );

    // And the new individual file will hold the final text

    file_put_contents( $extraFile , $text );
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
