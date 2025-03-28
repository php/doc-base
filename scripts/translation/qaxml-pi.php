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

Compare processing instructions usage between two XML files.          */

require_once __DIR__ . '/libqa/all.php';

$argv   = new ArgvParser( $argv );
$ignore = new OutputIgnore( $argv ); // may exit.
$argv->complete();

$list   = SyncFileList::load();

foreach ( $list as $file )
{
    $source = $file->sourceDir . '/' . $file->file;
    $target = $file->targetDir . '/' . $file->file;
    $output = new OutputBuffer( "# qaxml.p" , $target , $ignore );

    [ $s ] = XmlFrag::loadXmlFragmentFile( $source );
    [ $t ] = XmlFrag::loadXmlFragmentFile( $target );

    $s = XmlFrag::listNodes( $s , XML_PI_NODE );
    $t = XmlFrag::listNodes( $t , XML_PI_NODE );

    $s = extractPiData( $s );
    $t = extractPiData( $t );

    if ( implode( "\n" , $s ) === implode( "\n" , $t ) )
        continue;

    $sideCount = [];

    foreach( $s as $v )
        $sideCount[$v] = [ 0 , 0 ];
    foreach( $t as $v )
        $sideCount[$v] = [ 0 , 0 ];

    foreach( $s as $v )
        $sideCount[$v][0] += 1;
    foreach( $t as $v )
        $sideCount[$v][1] += 1;

    foreach( $sideCount as $k => $v )
        if ( $v[0] != $v[1] )
            $output->addDiff( $k , $v[0] , $v[1] );

    $output->print();
}

function extractPiData( array $list )
{
    $ret = [];
    foreach( $list as $elem )
        $ret[] = "{$elem->target} {$elem->data}";
    return $ret;
}
