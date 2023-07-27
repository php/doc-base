<?php

/**
 * qaxml.a.check.php -- Comparare attributes between XMLs
 */

require_once __DIR__ . '/lib/require.php';

$qalist = QaFileInfo::cacheLoad();

foreach ( $qalist as $qafile )
{
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

    $intersect = array_intersect( $s, $t );
    $onlySource = array_diff( $s , $intersect );
    $onlyTarget = array_diff( $t , $intersect );

    print "qaxml.a.check: {$source} {$target}\n\n";

    foreach( $onlyTarget as $only )
        print "- {$only}\n";
    foreach( $onlySource as $only )
        print "+ {$only}\n";

    if ( count( $onlySource ) == 0 && count( $onlyTarget ) == 0 )
    {
        for ( $i = 0 ; $i < count($s) ; $i++ )
            if ( $s[$i] != $t[$i] )
            {
                print "- {$t[$i]}\n";
                print "+ {$s[$i]}\n";
            }
    }

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