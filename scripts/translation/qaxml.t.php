<?php

/**
 * qaxml.t.php -- Compare tag count and contents between XMLs
 */

require_once __DIR__ . '/lib/require.php';

$qalist = QaFileInfo::cacheLoad();

$tags = array();
if ( count($argv) > 1 )
    $tags = explode( ',' , $argv[1] );

foreach ( $qalist as $qafile )
{
    if ( $qafile->sourceHash != $qafile->targetHash )
        continue;

    $output = false;
    $source = $qafile->sourceDir . '/' . $qafile->file;
    $target = $qafile->targetDir . '/' . $qafile->file;

    // Tag contents

    if ( count( $tags ) > 0 )
    {
        $s = XmlUtil::loadFile( $source );
        $t = XmlUtil::loadFile( $target );

        $s = XmlUtil::listNodeType( $s , XML_ELEMENT_NODE );
        $t = XmlUtil::listNodeType( $t , XML_ELEMENT_NODE );

        $s = extractTagContentList( $s , $tags );
        $t = extractTagContentList( $t , $tags );

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

                print "* {$tag} -{$sourceCount} +{$targetCount}\n";
            }
        }

        if ( $output )
            print "\n";
    }
}

function extractTagContentList( array $nodes , array $tags )
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
            $text = trim( $text );
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