<?php
/**
 *  +----------------------------------------------------------------------+
 *  | Copyright (c) 1997-2023 The PHP Group                                |
 *  +----------------------------------------------------------------------+
 *  | This source file is subject to version 3.01 of the PHP license,      |
 *  | that is bundled with this package in the file LICENSE, and is        |
 *  | available through the world-wide-web at the following url:           |
 *  | https://www.php.net/license/3_01.txt.                                |
 *  | If you did not receive a copy of the PHP license and are unable to   |
 *  | obtain it through the world-wide-web, please send a note to          |
 *  | license@php.net, so we can mail you a copy immediately.              |
 *  +----------------------------------------------------------------------+
 *  | Authors:     André L F S Bacci <ae php.net>                          |
 *  +----------------------------------------------------------------------+
 *  | Description: Compare PIs usage between XMLs.                         |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/lib/all.php';

$qalist = QaFileInfo::cacheLoad();

foreach ( $qalist as $qafile )
{
    if ( $qafile->file == "bookinfo.xml" )
        continue;
    if ( $qafile->sourceHash != $qafile->targetHash )
        continue;

    $source = $qafile->sourceDir . '/' . $qafile->file;
    $target = $qafile->targetDir . '/' . $qafile->file;

    $s = XmlUtil::loadFile( $source );
    $t = XmlUtil::loadFile( $target );

    $s = XmlUtil::listNodeType( $s , XML_PI_NODE );
    $t = XmlUtil::listNodeType( $t , XML_PI_NODE );

    $s = extractPiData( $s );
    $t = extractPiData( $t );

    if ( implode( "\n" , $s ) == implode( "\n" , $t ) )
        continue;

    $header = true;
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

        if ( $header )
        {
            print "qaxml.p: {$target}\n\n";
            $header = false;
        }

        print "* {$k} -{$v[1]} +{$v[0]}\n";
    }

    if ( ! $header )
        print "\n";
}

function extractPiData( array $list )
{
    $ret = array();
    foreach( $list as $elem )
        $ret[] = "{$elem->target} {$elem->data}";
    return $ret;
}
