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

Compare XML entities usage between two XML leaf/fragment files.       */

require_once __DIR__ . '/libqa/all.php';

$ignore = new OutputIgnore( $argv ); // always first, may exit.
$list = SyncFileList::load();

foreach ( $list as $file )
{
    $source = $file->sourceDir . '/' . $file->file;
    $target = $file->targetDir . '/' . $file->file;
    $output = new OutputBuffer( "# qaxml.e" , $target , $ignore );

    [ $_ , $s , $_ ] = XmlFrag::loadXmlFragmentFile( $source );
    [ $_ , $t , $_ ] = XmlFrag::loadXmlFragmentFile( $target );

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
