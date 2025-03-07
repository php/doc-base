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

Compare tags count/contents between two XML leaf/fragment files.      */

require_once __DIR__ . '/libqa/all.php';

$argv   = new ArgvParser( $argv );
$ignore = new OutputIgnore( $argv ); // may exit.
$detail = $argv->consume( "--detail" ) != null;
$tags   = explode( ',' , $argv->consume( prefix: "--content=" ) ?? "" );

$argv->complete();

if ( count( $tags ) == 1 && $tags[0] == "" )
    $tags = [];

if ( $detail )
    $ignore->appendIgnoreCommands = false;

$list   = SyncFileList::load();

foreach ( $list as $file )
{
    $source = $file->sourceDir . '/' . $file->file;
    $target = $file->targetDir . '/' . $file->file;
    $output = new OutputBuffer( "# qaxml.t" , $target , $ignore );

    if ( count( $tags ) > 0 )
    {
        // "Simple" tag contents check, by inner text

        [ $s , $_ , $_ ] = XmlFrag::loadXmlFragmentFile( $source );
        [ $t , $_ , $_ ] = XmlFrag::loadXmlFragmentFile( $target );

        $s = XmlFrag::listNodes( $s , XML_ELEMENT_NODE );
        $t = XmlFrag::listNodes( $t , XML_ELEMENT_NODE );

        typesNotCaseSensitive( $s );
        typesNotCaseSensitive( $t );

        $s = extractTagsInnerText( $s , $tags );
        $t = extractTagsInnerText( $t , $tags );

        if ( implode( "\n" , $s ) == implode( "\n" , $t ) )
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

        if ( $detail )
            foreach( $sideCount as $tag => $v )
                printTagUsageDetail( $source , $target , $tag , $output );

        $output->print( true );

        if ( $output->printCount )
            continue;

        // "Complex" tag contents check, by inner XML

        [ $s , $_ , $_ ] = XmlFrag::loadXmlFragmentFile( $source );
        [ $t , $_ , $_ ] = XmlFrag::loadXmlFragmentFile( $target );

        $s = XmlFrag::listNodes( $s , XML_ELEMENT_NODE );
        $t = XmlFrag::listNodes( $t , XML_ELEMENT_NODE );

        typesNotCaseSensitive( $s );
        typesNotCaseSensitive( $t );

        $s = extractTagsInnerXmls( $s , $tags );
        $t = extractTagsInnerXmls( $t , $tags );

        if ( implode( "\n" , $s ) == implode( "\n" , $t ) )
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

        if ( $detail )
            foreach( $sideCount as $tag => $v )
                printTagUsageDetail( $source , $target , $tag , $output );

        $output->print( true );
    }
    else
    {
        // Check tag count, not contents

        [ $s , $_ , $_ ] = XmlFrag::loadXmlFragmentFile( $source );
        [ $t , $_ , $_ ] = XmlFrag::loadXmlFragmentFile( $target );

        $s = XmlFrag::listNodes( $s , XML_ELEMENT_NODE );
        $t = XmlFrag::listNodes( $t , XML_ELEMENT_NODE );

        typesNotCaseSensitive( $s );
        typesNotCaseSensitive( $t );

        $s = extractNodeName( $s , $tags );
        $t = extractNodeName( $t , $tags );

        if ( implode( "\n" , $s ) == implode( "\n" , $t ) )
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

        if ( $detail )
            foreach( $sideCount as $tag => $v )
                printTagUsageDetail( $source , $target , $tag , $output );

        $output->print( true );
    }
}

function extractNodeName( array $list , array $tags )
{
    $ret = [];
    foreach( $list as $elem )
        if ( count( $tags ) == 0 || in_array( $elem->nodeName , $tags ) )
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
    $ret = [];
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
    $ret = [];
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

function printTagUsageDetail( string $source , string $target , string $tag , OutputBuffer $output )
{
    $source = collectTagLines( $source , $tag );
    $target = collectTagLines( $target , $tag );
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
            if ( abs( $s - $t ) < 5 )
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

function collectTagLines( string $file , string $tag )
{
    $ret = [];

    [ $s , $_ , $_ ] = XmlFrag::loadXmlFragmentFile( $file , false );
    $list = XmlFrag::listNodes( $s , XML_ELEMENT_NODE );

    foreach( $list as $node )
    {
        if ( $node->nodeName != $tag )
            continue;
        $ret[] = $node->getLineNo();
    }
    return $ret;
}
