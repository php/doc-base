<?php

/**
 * qaxml.p.php -- Compare PI usage between XMLs
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

    $s = XmlUtil::listNodeType( $s , XML_PI_NODE );
    $t = XmlUtil::listNodeType( $t , XML_PI_NODE );

    $s = extractPiData( $s );
    $t = extractPiData( $t );

    if ( implode( "\n" , $s ) == implode( "\n" , $t ) )
        continue;

    $intersect = array_intersect( $s, $t );
    $onlySource = array_diff( $s , $intersect );
    $onlyTarget = array_diff( $t , $intersect );

    print "qaxml.p: {$target}\n\n";

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

    print "\n";
}

function extractPiData( array $list )
{
    $ret = array();
    foreach( $list as $elem )
        $ret[] = "{$elem->target} {$elem->data}";
    return $ret;
}