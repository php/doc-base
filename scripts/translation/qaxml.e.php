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
 *  | Description: Compare entities usage between XMLs.                    |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/lib/all.php';

$qalist = QaFileInfo::cacheLoad();
$outarg = new OutputIgnoreArgv( $argv );

foreach ( $qalist as $qafile )
{
    if ( $qafile->sourceHash != $qafile->targetHash )
        continue;

    $source = $qafile->sourceDir . '/' . $qafile->file;
    $target = $qafile->targetDir . '/' . $qafile->file;

    $s = XmlUtil::extractEntities( $source );
    $t = XmlUtil::extractEntities( $target );

    if ( implode( "\n" , $s ) == implode( "\n" , $t ) )
        continue;

    $output = new OutputIgnoreBuffer( $outarg , "qaxml.e: {$target}\n\n" , $target );

    $match = [];

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

        $output->add( "* &{$k}; -{$v[1]} +{$v[0]}\n" );
    }

    $output->addLine();
    $output->print();
}
