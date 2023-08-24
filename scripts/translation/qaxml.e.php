<?php

/**
 * qaxml.e.php -- Compare entities usage between XMLs
 */

require_once __DIR__ . '/lib/all.php';

$qalist = QaFileInfo::cacheLoad();
$outarg = new OutputIgnoreArgv( $argv );

foreach ( $qalist as $qafile )
{
    if ( $qafile->file == "bookinfo.xml" )
        continue;
    if ( $qafile->sourceHash != $qafile->targetHash )
        continue;

    $source = $qafile->sourceDir . '/' . $qafile->file;
    $target = $qafile->targetDir . '/' . $qafile->file;

    $s = XmlUtil::extractEntities( $source );
    $t = XmlUtil::extractEntities( $target );

    if ( implode( "\n" , $s ) == implode( "\n" , $t ) )
        continue;

    $output = new OutputIgnoreBuffer( $outarg , "qaxml.e: {$target}\n\n" , $target );

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

        $output->add( "* &{$k}; -{$v[1]} +{$v[0]}\n" );
    }

    $output->addLine();
    $output->print();
}