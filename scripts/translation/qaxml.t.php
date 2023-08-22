<?php

/**
 * qaxml.t.php -- Compare tag count and contents between XMLs
 */

require_once __DIR__ . '/lib/all.php';

$tags = array();
$showDetail = false;
$showIgnore = true;

$igfile = new CacheFile( getcwd() . "/.qaxml.t.ignore" );

$cmd0 = array_shift( $argv );

while ( count( $argv ) > 0 )
{
    $arg = array_shift( $argv );

    if ( $arg == "--detail" )
    {
        $showDetail = true;
        continue;
    }

    if ( str_starts_with( $arg , "--add-ignore=" ) )
    {
        $ignore = $igfile->load( array() );
        if ( count( $ignore ) == 0 )
            print "Creating file ignore file on current working directory.\n";
        $add = substr( $arg , 13 );
        $ignore[] = $add;
        $igfile->save( $ignore );
        exit;
    }

    if ( str_starts_with( $arg , "--del-ignore=" ) )
    {
        $ignore = $igfile->load( array() );
        $del = substr( $arg , 13 );
        $key = array_search( $del , $ignore );

        if ( $key === false )
            print "Ignore mark not found.\n";
        else
            unset( $ignore[$key] );
        $igfile->save( $ignore );
        exit;
    }

    if ( str_starts_with( $arg , "--disable-ignore" ) )
    {
        $showIgnore = false;
        continue;
    }

    $tags = explode( ',' , $arg );
}

$qalist = QaFileInfo::cacheLoad();

foreach ( $qalist as $qafile )
{
    if ( $qafile->file == "bookinfo.xml" )
        continue;
    if ( $qafile->sourceHash != $qafile->targetHash )
        continue;

    $source = $qafile->sourceDir . '/' . $qafile->file;
    $target = $qafile->targetDir . '/' . $qafile->file;

    $output = new OutputBufferHasher( "qaxml.t: {$target}\n\n" );
    $ignore = $igfile->load( array() );

    // Check tag contents, inner text

    if ( count( $tags ) > 0 )
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
            $output->push( "* {$k} -{$v[1]} +{$v[0]}\n" );
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

        typesNotCaseSensitive( $s );
        typesNotCaseSensitive( $t );

        $s = extractTagsInnerXmls( $s , $tags );
        $t = extractTagsInnerXmls( $t , $tags );

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
            $output->push( "* {$k} -{$v[1]} +{$v[0]}\n" );
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

        typesNotCaseSensitive( $s );
        typesNotCaseSensitive( $t );

        $s = extractNodeName( $s , $tags );
        $t = extractNodeName( $t , $tags );

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
            $output->push( "* {$k} -{$v[1]} +{$v[0]}\n" );
        }

        $output->pushExtra( "\n" );
    }

    // Ignore

    if ( $showIgnore && $output->isEmpty() == false )
    {
        $prefix = $output->hash( $tags );
        $suffix = md5( implode( "" , $tags ) ) . ',' . $qafile->file;
        $mark = "{$prefix},{$suffix}";

        if ( in_array( $mark , $ignore ) )
            $output->clear();
        else
            $output->push( "  To ignore, run:\n    php $cmd0 --add-ignore=$mark\n" );

        while ( in_array( $mark , $ignore ) )
        {
            $key = array_search( $mark , $ignore );
            unset( $ignore[$key] );
        }
        foreach ( $ignore as $item )
            if ( str_ends_with( $item , $suffix ) )
                $output->push( "  Unused ignore. To drop, run:\n    php $cmd0 --del-ignore=$mark\n" );

        $output->pushExtra( "\n" );
    }

    // Output

    $output->print();
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

function printTagUsageDetail( string $source , string $target , string $tag , OutputBufferHasher $output )
{
    $output->push( "\n" );
    $s = collectTagDefinitions( $source , $tag );
    $t = collectTagDefinitions( $target , $tag );
    $min = min( count( $s ) , count( $t ) );
    for( $i = 0 ; $i < $min ; $i++ )
        $output->push( "\t{$tag}\t{$s[$i]}\t{$t[$i]}\n" );
    for( $i = $min ; $i < count($s) ; $i++ )
        $output->push( "\t{$tag}\t{$s[$i]}\t\t\n" );
    for( $i = $min ; $i < count($t) ; $i++ )
        $output->push( "\t{$tag}\t\t{$t[$i]}\t\n" );
    $output->push( "\n" );
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

class OutputBufferHasher
{
    public string $header = "";
    public array $texts = array();

    function __construct( string $header = "" )
    {
        $this->header = $header;
    }

    function clear()
    {
        $this->texts = array();
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
        if ( $this->isEmpty() )
            return;
        print $this->header;
        foreach( $this->texts as $text )
            print $text;
    }
}
