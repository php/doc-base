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

Compare attributes usage between two XML leaf/fragment files.         */

require_once __DIR__ . '/libqa/all.php';

$oklist = SyncFileList::load();
$ignore = new OutputIgnore( $argv );

foreach ( $oklist as $file )
{
    $source = $file->sourceDir . '/' . $file->file;
    $target = $file->targetDir . '/' . $file->file;
    $output = new OutputBuffer( "qaxml.a" , $file->file , $ignore );

    [ $s , $e ] = XmlFrag::loadXmlFragmentFile( $source );
    [ $t , $e ] = XmlFrag::loadXmlFragmentFile( $target );

    $s = XmlFrag::listNodes( $s , XML_ELEMENT_NODE );
    $t = XmlFrag::listNodes( $t , XML_ELEMENT_NODE );

    $s = extractTriple( $s );
    $t = extractTriple( $t );

    if ( implode( "\n" , $s ) == implode( "\n" , $t ) )
        continue;

    $match = array();

    foreach( $s as $v )
        $match[$v] = array( 0 , 0 );
    foreach( $t as $v )
        $match[$v] = array( 0 , 0 );

    foreach( $s as $v )
        $match[$v][0] += 1;
    foreach( $t as $v )
        $match[$v][1] += 1;

    foreach( $match as $k => $v )
    {
        if ( $v[0] == $v[1] )
            continue;

        $output->addDiff( $k , $v[0] , $v[1] );
    }

    $output->print();
}

function extractTriple( array $list )
{
    $ret = array();
    foreach( $list as $elem )
        foreach( $elem->attributes as $attrib )
            $ret[] = "{$elem->nodeName} {$attrib->nodeName} {$attrib->nodeValue}";
    return $ret;
}
