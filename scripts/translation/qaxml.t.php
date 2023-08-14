<?php

/**
 * qaxml.t.php -- Compare tag count and contents between XMLs
 */

require_once __DIR__ . '/lib/all.php';

$tags = array();
$showDetail = false;

array_shift( $argv );

if ( count( $argv ) > 0 && $argv[0] == '--detail' )
{
    $showDetail = true;
    array_shift( $argv );
}

if ( count( $argv ) > 0 )
    $tags = explode( ',' , $argv[0] );

$qalist = QaFileInfo::cacheLoad();

foreach ( $qalist as $qafile )
{
    if ( $qafile->file == "bookinfo.xml" )
        continue;
    if ( $qafile->sourceHash != $qafile->targetHash )
        continue;

    $output = false;
    $source = $qafile->sourceDir . '/' . $qafile->file;
    $target = $qafile->targetDir . '/' . $qafile->file;

    // Tag contents, text

    if ( count( $tags ) > 0 )
    {
        $s = XmlUtil::loadFile( $source );
        $t = XmlUtil::loadFile( $target );

        $s = XmlUtil::listNodeType( $s , XML_ELEMENT_NODE );
        $t = XmlUtil::listNodeType( $t , XML_ELEMENT_NODE );

        $s = extractTagsInnerText( $s , $tags );
        $t = extractTagsInnerText( $t , $tags );

        $intersect = array_intersect( $s, $t );
        $onlySource = array_diff( $s , $intersect );
        $onlyTarget = array_diff( $t , $intersect );

        if ( count( $s ) == count( $t ) && count( $onlySource ) == 0 && count( $onlyTarget ) == 0 )
            continue;

        if ( ! $output )
        {
            print "qaxml.t: {$target}\n\n";
            $output = true;
        }

        foreach( $onlyTarget as $only )
            print "- {$only}\n";
        foreach( $onlySource as $only )
            print "+ {$only}\n";

        if ( count( $onlySource ) == 0 && count( $onlyTarget ) == 0 )
        {
            $s = array_count_values( $s );
            $t = array_count_values( $t );
            foreach ($s as $key => $countSource )
            {
                $countTarget = $t[$key];
                $countDiff = $countSource - $countTarget;
                if ( $countDiff > 0 )
                    print "* {$key} +{$countDiff}\n";
                if ( $countDiff < 0 )
                    print "* {$key} {$countDiff}\n";
            }
        }

        if ( $output )
        {
            print "\n";
            continue;
        }
    }

    // Tag contents, XML

    if ( count( $tags ) > 0 && ! $output )
    {
        $s = XmlUtil::loadFile( $source );
        $t = XmlUtil::loadFile( $target );

        $s = XmlUtil::listNodeType( $s , XML_ELEMENT_NODE );
        $t = XmlUtil::listNodeType( $t , XML_ELEMENT_NODE );

        $s = extractTagsInnerXmls( $s , $tags );
        $t = extractTagsInnerXmls( $t , $tags );

        $intersect = array_intersect( $s, $t );
        $onlySource = array_diff( $s , $intersect );
        $onlyTarget = array_diff( $t , $intersect );

        if ( count( $s ) == count( $t ) && count( $onlySource ) == 0 && count( $onlyTarget ) == 0 )
            continue;

        if ( ! $output )
        {
            print "qaxml.t: {$target}\n\n";
            $output = true;
        }

        foreach( $onlyTarget as $only )
            print "- {$only}\n";
        foreach( $onlySource as $only )
            print "+ {$only}\n";

        if ( count( $onlySource ) == 0 && count( $onlyTarget ) == 0 )
        {
            $s = array_count_values( $s );
            $t = array_count_values( $t );
            foreach ($s as $key => $countSource )
            {
                $countTarget = $t[$key];
                $countDiff = $countSource - $countTarget;
                if ( $countDiff > 0 )
                    print "* {$key} +{$countDiff}\n";
                if ( $countDiff < 0 )
                    print "* {$key} {$countDiff}\n";
            }
        }

        if ( $output )
        {
            print "\n";
            continue;
        }
    }

    // Tag count
    {
        $s = XmlUtil::loadFile( $source );
        $t = XmlUtil::loadFile( $target );

        $s = XmlUtil::listNodeType( $s , XML_ELEMENT_NODE );
        $t = XmlUtil::listNodeType( $t , XML_ELEMENT_NODE );

        $s = countTags( $s );
        $t = countTags( $t );

        equalizeKeys( $s , $t );
        equalizeKeys( $t , $s );

        foreach( $s as $tag => $sourceCount )
        {
            $targetCount = $t[$tag];

            if ( $sourceCount != $targetCount )
            {
                if ( ! $output )
                {
                    print "qaxml.t: {$target}\n\n";
                    $output = true;
                }

                print "* {$tag} -{$targetCount} +{$sourceCount}\n";

                if ( $showDetail )
                    printTagUsageDetail( $source , $target , $tag );
            }
        }

        if ( $output )
            print "\n";
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
        // Types not case-sensitive: https://github.com/php/doc-en/issues/2658
        if ( $tag == "type" )
        {
            switch( strtolower( $text ) )
            {
                case "array":
                case "string":
                case "float":
                case "bool":
                case "null":
                    $text = strtolower( $text );
                    break;
            }
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

function countTags( array $list )
{
    $ret = array();
    foreach( $list as $elem )
        $ret[] = $elem->nodeName;
    $ret = array_count_values( $ret );
    return $ret;
}

function equalizeKeys( array $list , array & $other , mixed $value = 0 )
{
    foreach( $list as $k => $v )
        if ( ! isset( $other[$k] ) )
            $other[$k] = $value;
}

function printTagUsageDetail( string $source , string $target , string $tag )
{
    print "\n";
    $s = collectTagDefinitions( $source , $tag );
    $t = collectTagDefinitions( $target , $tag );
    $min = min( count( $s ) , count( $t ) );
    for( $i = 0 ; $i < $min ; $i++ )
    {
        $d = $s[$i] - $t[$i];
        print "\t{$tag}\t{$s[$i]}\t{$t[$i]}\t{$d}\n";
    }
    for( $i = $min ; $i < count($s) ; $i++ )
        print "\t{$tag}\t{$s[$i]}\t\t\n";
    for( $i = $min ; $i < count($t) ; $i++ )
        print "\t{$tag}\t\t{$t[$i]}\t\n";
    print "\n";
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