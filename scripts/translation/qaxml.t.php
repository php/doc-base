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
 *  | Description: Compare tag count and contents between XMLs.            |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/lib/all.php';

$tags = array();
$showDetail = false;

$qalist = QaFileInfo::cacheLoad();
$outarg = new OutputIgnoreArgv( $argv );

array_shift( $argv );
while ( count( $argv ) > 0 )
{
    $arg = array_shift( $argv );

    if ( $arg == "--detail" )
    {
        $showDetail = true;
        $outarg->showIgnore = false;
        continue;
    }

    $tags = explode( ',' , $arg );
}

foreach ( $qalist as $qafile )
{
    if ( $qafile->sourceHash != $qafile->targetHash )
        continue;

    $source = $qafile->sourceDir . '/' . $qafile->file;
    $target = $qafile->targetDir . '/' . $qafile->file;

    $output = new OutputIgnoreBuffer( $outarg , "qaxml.t: {$target}\n\n" , $target );

    // First check, by tag contents, inner text

    if ( count( $tags ) > 0 && $output->printCount == 0 )
    {
        $s = XmlUtil::loadFile( $source );
        $t = XmlUtil::loadFile( $target );

        $s = XmlUtil::listNodeType( $s , XML_ELEMENT_NODE );
        $t = XmlUtil::listNodeType( $t , XML_ELEMENT_NODE );

        typesNotCaseSensitive( $s );
        typesNotCaseSensitive( $t );

        $s = extractTagsInnerText( $s , $tags );
        $t = extractTagsInnerText( $t , $tags );

        $match = array();

        foreach( $t as $v )
            $match[$v] = array( 0 , 0 );
        foreach( $s as $v )
            $match[$v] = array( 0 , 0 );

        foreach( $s as $v )
            $match[$v][0] += 1;
        foreach( $t as $v )
            $match[$v][1] += 1;

        foreach( $match as $k => $v )
            $output->addDiff( $k , $v[0] , $v[1] );

        if ( $showDetail )
            foreach( $match as $tag => $v )
                printTagUsageDetail( $source , $target , $tag , $output );

        $output->print( true );
    }

    // Second check, by tag contents, inner XML

    if ( count( $tags ) > 0 && $output->printCount == 0 )
    {
        $s = XmlUtil::loadFile( $source );
        $t = XmlUtil::loadFile( $target );

        $s = XmlUtil::listNodeType( $s , XML_ELEMENT_NODE );
        $t = XmlUtil::listNodeType( $t , XML_ELEMENT_NODE );

        typesNotCaseSensitive( $s );
        typesNotCaseSensitive( $t );

        $s = extractTagsInnerXmls( $s , $tags );
        $t = extractTagsInnerXmls( $t , $tags );

        $match = array();

        foreach( $t as $v )
            $match[$v] = array( 0 , 0 );
        foreach( $s as $v )
            $match[$v] = array( 0 , 0 );

        foreach( $s as $v )
            $match[$v][0] += 1;
        foreach( $t as $v )
            $match[$v][1] += 1;

        foreach( $match as $k => $v )
            $output->addDiff( $k , $v[0] , $v[1] );

        if ( $showDetail )
            foreach( $match as $tag => $v )
                printTagUsageDetail( $source , $target , $tag , $output );

        $output->print( true );
    }

    // Last check, simple tag count

    if ( $output->printCount == 0 )
    {
        $s = XmlUtil::loadFile( $source );
        $t = XmlUtil::loadFile( $target );

        $s = XmlUtil::listNodeType( $s , XML_ELEMENT_NODE );
        $t = XmlUtil::listNodeType( $t , XML_ELEMENT_NODE );

        typesNotCaseSensitive( $s );
        typesNotCaseSensitive( $t );

        $s = extractNodeName( $s , $tags );
        $t = extractNodeName( $t , $tags );

        $match = array();

        foreach( $t as $v )
            $match[$v] = array( 0 , 0 );
        foreach( $s as $v )
            $match[$v] = array( 0 , 0 );

        foreach( $s as $v )
            $match[$v][0] += 1;
        foreach( $t as $v )
            $match[$v][1] += 1;

        foreach( $match as $k => $v )
            $output->addDiff( $k , $v[0] , $v[1] );

        if ( $showDetail )
            foreach( $match as $tag => $v )
                printTagUsageDetail( $source , $target , $tag , $output );

        $output->print( true );
    }
}

function extractNodeName( array $list , array $tags )
{
    $ret = array();
    foreach( $list as $elem )
        if ( in_array( $elem->nodeName , $tags) || count( $tags ) == 0 )
            $ret[] = $elem->nodeName;
    return $ret;
}

function typesNotCaseSensitive( array & $nodes )
{
    // Types not case-sensitive: https://github.com/php/doc-en/issues/2658

    if ( $nodes == null )
        return;

    foreach( $nodes as $node )
    {
        if ( $node->nodeName == "type" )
        {
            $text = trim( strtolower( $node->nodeValue ) );
            switch( $text )
            {
                case "array":
                case "string":
                case "float":
                case "bool":
                case "null":
                    $node->nodeValue = $text;
                    break;
            }
        }
    }
}

function extractTagsInnerText( array $nodes , array $tags )
{
    $ret = array();
    foreach( $nodes as $node )
    {
        $tag = $node->nodeName;
        if ( in_array( $tag , $tags ) == false )
            continue;
        $text = $node->textContent;
        while( true )
        {
            $was = strlen( $text );
            $text = str_replace( "\n" , " " , $text );
            $text = str_replace( "\r" , " " , $text );
            $text = str_replace( "  " , " " , $text );
            if ( strlen( $text ) == $was )
                break;
        }
        $ret[] = $tag . ">"  . $text;
    }
    return $ret;
}

function extractTagsInnerXmls( array $nodes , array $tags )
{
    $ret = array();
    foreach( $nodes as $node )
    {
        $tag = $node->nodeName;
        if ( in_array( $tag , $tags ) == false )
            continue;
        $text = $node->ownerDocument->saveXML( $node );
        while( true )
        {
            $was = strlen( $text );
            $text = str_replace( "\n" , " " , $text );
            $text = str_replace( "\r" , " " , $text );
            $text = str_replace( "  " , " " , $text );
            if ( strlen( $text ) == $was )
                break;
        }
        $ret[] = $text;
    }
    return $ret;
}

function printTagUsageDetail( string $source , string $target , string $tag , OutputIgnoreBuffer $output )
{
    $source = collectTagDefinitions( $source , $tag );
    $target = collectTagDefinitions( $target , $tag );
    if ( count( $source ) == count($target) )
        return;
    $output->addLine();
    $s = null;
    $t = null;
    while ( count( $source ) > 0 || count( $target ) > 0 )
    {
        if ( $s == null )
            $s = array_shift( $source );
        if ( $t == null )
            $t = array_shift( $target );
        if ( $s != null && $t != null )
        {
            if ( abs( $s - $t ) < 1 )
            {
                $output->add( "\t{$tag}\t{$s}\t{$t}\n" );
                $s = null;
                $t = null;
                continue;
            }
            if ( $s < $t )
            {
                array_unshift( $target , $t );
                $t = null;
            }
            else
            {
                array_unshift( $source , $s );
                $s = null;
            }
        }
        if ( $s != null )
        {
            $output->add( "\t{$tag}\t{$s}\t-\n" );
            $s = null;
        }
        if ( $t != null )
        {
            $output->add( "\t{$tag}\t-\t{$t}\n" );
            $t = null;
        }
    }
    $output->addLine();
}

function collectTagDefinitions( string $file , string $tag )
{
    $ret = array();
    $text = XmlUtil::loadFile( $file );
    $list = XmlUtil::listNodeType( $text , XML_ELEMENT_NODE );
    foreach( $list as $node )
    {
        if ( $node->nodeName != $tag )
            continue;
        $ret[] = $node->getLineNo();
    }
    return $ret;
}
