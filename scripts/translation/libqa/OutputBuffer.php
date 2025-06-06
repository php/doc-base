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

    public int $printCount = 0;

    public function __construct( string $header , string $filename , OutputIgnore $ignore )
    {
        $filename = str_replace( "/./" , "/" , $filename );

        $this->header = $header . ": " . $filename . "\n\n";
        $this->filename = $filename;
        $this->ignore = $ignore;
    }

    public function add( string $text )
    {
        $this->matter[] = $text;
    }

    public function addDiff( string $text , int $sourceCount , int $targetCount )
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

    public function addFooter( string $text )
    {
        $this->footer[] = $text;
    }

    public function addLine()
    {
        if ( count( $this->matter ) > 0 && end( $this->matter ) != "\n" )
            $this->add( "\n" );
    }

    public function contains( string $text ) : bool
    {
        foreach( $this->matter as $line )
            if ( str_contains( $line , $text ) )
                return true;
        return false;
    }

    public function print( bool $alternatePrinting = false )
    {
        if ( count( $this->matter ) == 0 && count( $this->footer ) == 0 )
            return;

        $this->printCount++;

        $hashFile = hash( "crc32b" , $this->filename );
        $hashHead = $this->hash( false );
        $hashFull = $this->hash( true );

        if ( $this->ignore->shouldIgnore( $this , $hashFile , $hashHead , $hashFull ) )
            return;

        print $this->header;

        if ( $alternatePrinting )
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

        print "\n";
    }

    private function printMatterAlternate() : void
    {
        $add = [];
        $del = [];
        $rst = [];

        foreach( $this->matter as $text )
        {
            if     ( $text[0] == '+' ) $add[] = $text;
            elseif ( $text[0] == '-' ) $del[] = $text;
            else                       $rst[] = $text;
        }

        for ( $idx = 0 ; $idx < count( $this->matter ) ; $idx++ )
        {
            if ( isset( $del[ $idx ] ) ) print $del[ $idx ];
            if ( isset( $add[ $idx ] ) ) print $add[ $idx ];
        }

        foreach( $rst as $text )
            print $text;
    }

    private function hash( bool $withContents ) : string
    {
        $text = $this->header;
        $text .= implode( "" , array_filter( $this->ignore->argvRest ) );
        if ( $withContents )
            $text .= implode( "" , $this->matter );
        $text = str_replace( " " , "" , $text );
        $text = str_replace( "\n" , "" , $text );
        $text = str_replace( "\r" , "" , $text );
        $text = str_replace( "\t" , "" , $text );
        return hash( "crc32b" , $text );
    }
}
