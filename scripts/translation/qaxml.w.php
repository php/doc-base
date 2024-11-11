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
 *  | Description: Checks for ws that may cause render trouble.            |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/lib/all.php';

$qalist = QaFileInfo::cacheLoad();
$outarg = new OutputIgnoreArgv( $argv );

foreach ( $qalist as $qafile )
{
    $source = $qafile->sourceDir . '/' . $qafile->file;
    $target = $qafile->targetDir . '/' . $qafile->file;

    whitespaceCheckFile( $source );
    whitespaceCheckFile( $target );
}

function whitespaceCheckFile( string $filename )
{
    if ( file_exists( $filename ) == false )
        return;

    global $outarg;
    $output = new OutputIgnoreBuffer( $outarg , "qaxml.w: {$filename}\n\n" , $filename );

    $xml = XmlUtil::loadFile( $filename );
    $tags = XmlUtil::listNodeType( $xml , XML_ELEMENT_NODE );
    
    foreach( $tags as $node )
    {
        switch ( $node->nodeName )
        {
            case "classname":
            case "constant":
            case "function":
            case "methodname":
            case "varname":
                $text = $node->nodeValue;
                $trim = trim( $text );
                if ( $text != $trim )
                {
                    $output->addLine();
                    $output->add( "  {$node->nodeName} {$trim}\n" );
                }
                break;
        }
    }

    $output->print();
}

