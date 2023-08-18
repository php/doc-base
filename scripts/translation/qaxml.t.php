<?php

/**
 * qaxml.t.php -- Compare tag count and contents between XMLs
 */

require_once __DIR__ . '/lib/all.php';

$tags = array();
$showDetail = false;
$showIgnore = false;

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

    $source = $qafile->sourceDir . '/' . $qafile->file;
    $target = $qafile->targetDir . '/' . $qafile->file;

    $output = new TextBufferHasher( "qaxml.t: {$target}\n\n" );

    // Check tag contents, inner text

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

        $showIgnore = true;

        foreach( $onlyTarget as $only )
            $output->push( "- {$only}\n" );
        foreach( $onlySource as $only )
            $output->push( "+ {$only}\n" );

        if ( count( $onlySource ) == 0 && count( $onlyTarget ) == 0 )
        {
            $s = array_count_values( $s );
            $t = array_count_values( $t );
            foreach ($s as $key => $countSource )
            {
                $countTarget = $t[$key];
                $countDiff = $countSource - $countTarget;
                if ( $countDiff > 0 )
                    $output->push( "* {$key} +{$countDiff}\n" );
                if ( $countDiff < 0 )
                    $output->push( "* {$key} {$countDiff}\n" );
            }
        }

        $output->pushExtra( "\n" );
    }

    // Check tag contents, inner XML

    if ( count( $tags ) > 0 && $output->isEmpty() )
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

        $showIgnore = true;

        foreach( $onlyTarget as $only )
            $output->push( "- {$only}\n" );
        foreach( $onlySource as $only )
            $output->push( "+ {$only}\n" );

        if ( count( $onlySource ) == 0 && count( $onlyTarget ) == 0 )
        {
            $s = array_count_values( $s );
            $t = array_count_values( $t );
            foreach ($s as $key => $countSource )
            {
                $countTarget = $t[$key];
                $countDiff = $countSource - $countTarget;
                if ( $countDiff > 0 )
                    $output->push( "* {$key} +{$countDiff}\n" );
                if ( $countDiff < 0 )
                    $output->push( "* {$key} {$countDiff}\n" );
            }
        }

        $output->pushExtra( "\n" );
    }

    // Check tag count

    if ( $output->isEmpty() )
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
                $showIgnore = false;
                $output->push( "* {$tag} -{$targetCount} +{$sourceCount}\n" );

                if ( $showDetail )
                    printTagUsageDetail( $source , $target , $tag );
            }
        }
        $output->pushExtra( "\n" );
    }

    if ( $showIgnore )
    {
        $hash = $output->hash();
        $mark = "qaxml.t.php ignore $hash";

        $marks = XmlUtil::listNodeType( XmlUtil::loadFile( $target ) , XML_COMMENT_NODE );
        foreach( $marks as $k => $v )
            $marks[$k] = trim( $v->nodeValue );
        $key = array_search( $mark , $marks );

        if ( $key === false )
            $output->push( "  To ignore, annotate with: <!-- $mark -->\n" );
        else
            unset( $marks[$key] );

        foreach ( $marks as $item )
            if ( str_starts_with( $item , "qaxml.t.php ignore" ) )
                $output->push( "  Unconsumed annotation: $item\n" );

        $output->pushExtra( "\n" );
    }

    if ( $output->isEmpty() == false )
        $output->print();
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

class TextBufferHasher
{
    public string $header = "";
    public array $texts = array();

    function __construct( string $header = "" )
    {
        $this->header = $header;
    }

    function hash() : string
    {
        if ( count( $this->texts) == 0 )
            return "";
        $text = $this->header . implode( "" , $this->texts );
        $text = str_replace( " " , "" , $text );
        $text = str_replace( "\n" , "" , $text );
        $text = str_replace( "\r" , "" , $text );
        $text = str_replace( "\t" , "" , $text );
        return md5( $text );
    }

    function isEmpty() : bool
    {
        return count( $this->texts ) == 0;
    }

    function push( string $text )
    {
        $this->texts[] = $text;
    }

    function pushExtra( string $text )
    {
        if ( count( $this->texts ) > 0 )
            $this->push( $text );
    }

    function print()
    {
        print $this->header;
        foreach( $this->texts as $text )
            print $text;
    }
}
