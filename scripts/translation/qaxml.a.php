<?php

/**
 * qaxml.a.php -- Compare attributes between XMLs
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

    $s = XmlUtil::listNodeType( $s , XML_ELEMENT_NODE );
    $t = XmlUtil::listNodeType( $t , XML_ELEMENT_NODE );

    $s = extractTriple( $s );
    $t = extractTriple( $t );

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
            print "qaxml.a: {$target}\n\n";
            $header = false;
        }

        print "* {$k} -{$v[1]} +{$v[0]}\n";
    }

    if ( ! $header )
        print "\n";
}

function extractTriple( array $list )
{
    $ret = array();
    foreach( $list as $elem )
        foreach( $elem->attributes as $attrib )
            $ret[] = "{$elem->nodeName} {$attrib->nodeName} {$attrib->nodeValue}";
    return $ret;
}