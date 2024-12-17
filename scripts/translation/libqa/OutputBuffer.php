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

This class caches formatted output, and calculates if this output is not
previously marked as ignored, before printing it.                     */

class OutputBuffer
{
    private string $filename = "";
    private string $header = "";
    private array  $matter = [];
    private array  $footer = [];

    private OutputIgnore $ignore;
    private string $options;

    function __construct( array $argv , string $filename , string $header )
    {
        $this->header = $header;
        $this->filename = $filename;
        $this->ignore = new OutputIgnore( $argv );

        $copy = $argv;
        array_shift( $copy );
        $this->options = implode( " " , $copy );
    }

    function add( string $text )
    {
        $this->matter[] = $text;
    }

    function addDiff( string $text , int $sourceCount , int $targetCount )
    {
        if ( $sourceCount == $targetCount )
            return;
        $prefix = "* ";
        $suffix = " -{$targetCount} +{$sourceCount}";
        if ( $sourceCount == 0 )
        {
            $prefix = "- ";
            $suffix = $targetCount == 1 ? "" : " -{$targetCount}";
        }
        if ( $targetCount == 0 )
        {
            $prefix = "+ ";
            $suffix = $sourceCount == 1 ? "" : " +{$sourceCount}";
        }
        $this->add( "{$prefix}{$text}{$suffix}\n" );
    }

    function addFooter( string $text )
    {
        $this->footer[] = $text;
    }

    function addLine()
    {
        if ( count( $this->matter ) > 0 && end( $this->matter ) != "\n" )
            $this->add( "\n" );
    }

    function print( bool $useAlternatePrinting = false )
    {
        if ( count( $this->matter ) == 0 )
            return;
        $this->printCount++;

        if ( $this->ignore->shouldIgnore( $this ) )
            return;

        if ( count( $this->matter ) == 0 && count( $this->footer ) == 0 )
            return;

        print $this->header;

        if ( $useAlternatePrinting )
            $this->printMatterAlternate();
        else
            foreach( $this->matter as $text )
                print $text;

        if ( count( $this->matter ) )
            print "\n";

        foreach( $this->footer as $text )
            print $text;

        if ( count( $this->footer ) )
            print "\n";
    }

    private function printMatterAlternate() : void
    {
        $add = array();
        $del = array();
        $rst = array();

        foreach( $this->matter as $text )
        {
            if     ( $text[0] == '+' ) $add[] = $text;
            elseif ( $text[0] == '-' ) $del[] = $text;
            else                       $rst[] = $text;
        }

        for ( $idx = 0 ; $idx < count( $this->matter ) ; $idx++ )
        {
            if ( isset( $add[ $idx ] ) ) print $add[ $idx ];
            if ( isset( $del[ $idx ] ) ) print $del[ $idx ];
        }

        foreach( $rst as $text )
            print $text;
    }

    private function hash( bool $withContents ) : string
    {
        $text = $this->header . $this->ignore->options;
        if ( $withContents )
            $text .= implode( "" , $this->matter );
        $text = str_replace( " " , "" , $text );
        $text = str_replace( "\n" , "" , $text );
        $text = str_replace( "\r" , "" , $text );
        $text = str_replace( "\t" , "" , $text );
        return hash( "crc32b" , $text );
    }
}
