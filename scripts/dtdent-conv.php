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
| Description: Convert old style .ent into new style .ent XML bundle.  |
+----------------------------------------------------------------------+

See `entities.php` source for detailed rationale.

Use this for converting bundled entities files that use <!ENTITY> into
XML version used by `entities.php`.

After converting, add the generated entities in an global.ent or
manual.ent file, and delete the previous one.

After all old style .ent files are split or converted, this script can
be removed. */

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );

if ( count( $argv ) < 2 )
     die(" Syntax: php $argv[0] infile\n" );

$infile = $argv[1];

$content = file_get_contents( $infile );

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

    // weird &ugly; ass, namespace corret, DOMDocumentFragment -> DOMNodeList (ampunstand intended)

    $name = trim( $name );
    $text = str_replace( "&" , "&amp;" , $text );

    $frag  = "<entities xmlns='http://docbook.org/ns/docbook' xmlns:xlink='http://www.w3.org/1999/xlink'>\n";
    $frag .= " <entity name='$name'>$text</entity>\n";
    $frag .= '</entities>';

    $dom = new DOMDocument( '1.0' , 'utf8' );
    $dom->recover = true;
    $dom->resolveExternals = false;
    libxml_use_internal_errors( true );

    $dom->loadXML( $frag , LIBXML_NSCLEAN );
    $dom->normalizeDocument();

    libxml_clear_errors();

    $text = $dom->saveXML( $dom->getElementsByTagName( "entity" )[0] );
    $text = str_replace( "&amp;" , "&" , $text );

    echo "$text\n";
}
