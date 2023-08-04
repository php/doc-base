<?php

/**
 * qaxml.e.php -- Compare entities usage between XMLs
 */

require_once __DIR__ . '/lib/require.php';

$qalist = QaFileInfo::cacheLoad();

foreach ( $qalist as $qafile )
{
    if ( $qafile->sourceHash != $qafile->targetHash )
        continue;

    $source = $qafile->sourceDir . '/' . $qafile->file;
    $target = $qafile->targetDir . '/' . $qafile->file;

    $s = XmlUtil::extractEntities( $source );
    $t = XmlUtil::extractEntities( $target );

    if ( implode( "\n" , $s ) == implode( "\n" , $t ) )
        continue;

    $intersect = array_intersect( $s, $t );
    $onlySource = array_diff( $s , $intersect );
    $onlyTarget = array_diff( $t , $intersect );

    print "qaxml.e: {$target}\n\n";

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