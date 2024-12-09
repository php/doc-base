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
| Authors:     André L F S Bacci <ae php.net>                          |
+----------------------------------------------------------------------+
| Description: Split old DTD .ent file into individual XML files.      |
+----------------------------------------------------------------------+

See `entities.php` for detailed rationale.

Use this for spliting `language-snippets-ent` and possible other DTD
entities files into individual .xml files.

After spliting, add generated files under doc-lang/entities/ , and
the original file, in one go.

After all DTD .ent files are split or converted, this script can
be removed. */

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );

if ( count( $argv ) < 3 )
     die("  Syntax: php $argv[0] infile outdir [hash user]\n" );

$infile = $argv[1];
$outdir = $argv[2];
$hash   = $argv[3] ?? "";
$user   = $argv[4] ?? "_";

$content = file_get_contents( $infile );
$entities = [];

// Parse

$pos1 = 0;
while ( true )
{
    $pos1 = strpos( $content , "<!ENTITY", $pos1 );
    if ( $pos1 === false ) break;

    $posS = strpos( $content , "'" , $pos1 );
    $posD = strpos( $content , '"' , $pos1 );

    if ( $posS < $posD )
        $q = "'";
    else
        $q = '"';

    $pos1 += 8;
    $pos2 = min( $posS , $posD ) + 1;
    $pos3 = strpos( $content , $q , $pos2 );

    $name = substr( $content , $pos1 , $pos2 - $pos1 - 1 );
    $text = substr( $content , $pos2 , $pos3 - $pos2 );

    $name = trim( $name );

    $entities[$name] = $text;
}

// Check

foreach( $entities as $name => $text )
{
    $file = "$outdir/$name.xml";
    if ( file_exists( $file ) )
        echo( "Entity name colision, OVERWROTE: $file\n" );
}

// Write

foreach( $entities as $name => $text )
{
    $file = "$outdir/$name.xml";

    if ( $hash == "" )
        $header = '<!-- $Revision$ -->';
    else
        $header .= "<!-- EN-Revision: $hash Maintainer: $user Status: ready --><!-- CREDITS: $user -->\n";

    file_put_contents( $file , $header . $text );
}

// Test

$dom = new DOMDocument();
$dom->recover = true;
$dom->resolveExternals = false;
libxml_use_internal_errors( true );

foreach( $entities as $name => $text )
{
    $file = "$outdir/$name.xml";

    $text = file_get_contents( $file );
    $text = "<frag>$text</frag>";

    $dom->loadXML( $text );
    $err = libxml_get_errors();
    libxml_clear_errors();

    foreach( $err as $e )
    {
        $msg = trim( $e->message );
        if ( str_starts_with( $msg , "Entity '" ) && str_ends_with( $msg , "' not defined" ) )
            continue;
        die( "Failed to load $file\n" );
    }
}

$total = count( $entities );
print "Generated $total files.\n";
