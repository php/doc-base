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

Inspect white space usage inside some known tags. Spurious whitespace
may break manual linking or generate visible artifacts.               */

require_once __DIR__ . '/libqa/all.php';

$argv   = new ArgvParser( $argv );
$ignore = new OutputIgnore( $argv ); // may exit.
$argv->complete();

$list   = SyncFileList::load();

foreach ( $list as $file )
{
    $source = $file->sourceDir . '/' . $file->file;
    $target = $file->targetDir . '/' . $file->file;

    whitespaceCheckFile( $source , $ignore );
    whitespaceCheckFile( $target , $ignore );
}

function whitespaceCheckFile( string $filename , OutputIgnore $ignore )
{
    if ( file_exists( $filename ) == false )
        return;

    $output = new OutputBuffer( "# qaxml.w" , $filename , $ignore );

    [ $xml , $_ , $_ ] = XmlFrag::loadXmlFragmentFile( $filename );
    $nodes = XmlFrag::listNodes( $xml , XML_ELEMENT_NODE );

    foreach( $nodes as $node )
    {
        switch ( $node->nodeName )
        {
            case "classname":
            case "constant":
            case "function":
            case "methodname":
            case "varname":
                $text = $node->nodeValue;
                $trim = trim( $text );
                if ( $text != $trim )
                {
                    $output->addLine();
                    $output->add( "  {$node->nodeName} {$trim}\n" );
                }
                break;
        }
    }

    $output->print();
}
