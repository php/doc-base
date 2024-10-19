<?php
/**
 *  +----------------------------------------------------------------------+
 *  | Copyright (c) 1997-2023 The PHP Group                                |
 *  +----------------------------------------------------------------------+
 *  | This source file is subject to version 3.01 of the PHP license,      |
 *  | that is bundled with this package in the file LICENSE, and is        |
 *  | available through the world-wide-web at the following url:           |
 *  | https://www.php.net/license/3_01.txt.                                |
 *  | If you did not receive a copy of the PHP license and are unable to   |
 *  | obtain it through the world-wide-web, please send a note to          |
 *  | license@php.net, so we can mail you a copy immediately.              |
 *  +----------------------------------------------------------------------+
 *  | Authors:     André L F S Bacci <ae php.net>                          |
 *  +----------------------------------------------------------------------+
 *  | Description: Cache the output and shows if if not marked to ignore.  |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class OutputIgnoreBuffer
{
    public int $printCount = 0;

    private string $filename = "";
    private string $header = "";
    private array  $texts  = array();
    private array  $footer = array();

    private OutputIgnoreArgv $args;

    function __construct( OutputIgnoreArgv $args , string $header , string $filename )
    {
        $this->args = $args;
        $this->header = $header;
        $this->filename = $filename;
    }

    function add( string $text )
    {
        $this->texts[] = $text;
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
        if ( count( $this->texts ) > 0 && end( $this->texts ) != "\n" )
            $this->add( "\n" );
    }

    function print()
    {
        if ( count( $this->texts ) == 0 )
            return;

        $this->addLine( "\n" );
        if ( count ( $this->texts ) > 0 )
             $this->printCount++;

        // footer

        $markhead = $this->filename . ':' . $this->hash( false ) . ':';
        $markfull = $markhead . $this->hash( true );
        $marks = OutputIgnoreArgv::cacheFile()->load( array() );

        if ( $this->args->showIgnore )
        {
            // --add-ignore

            if ( in_array( $markfull , $marks ) )
                $this->texts = array();
            else
                $this->args->pushAddIgnore( $this , $markfull );

            // remove duplicates

            while ( in_array( $markfull , $marks ) )
            {
                $key = array_search( $markfull , $marks );
                unset( $marks[$key] );
            }

            // --del-ignore

            foreach ( $marks as $mark )
                if ( $mark != null )
                    if ( str_starts_with( $mark , $markhead ) )
                        $this->args->pushDelIgnore( $this , $mark );
        }

        $this->addLine( "\n" );

        if ( count( $this->texts ) == 0 )
            return;

        print $this->header;

        foreach( $this->texts as $text )
            print $text;

        if ( count( $this->footer ) )
        {
            foreach( $this->footer as $text )
                print $text;
            print "\n";
        }
    }

    private function hash( bool $withContents ) : string
    {
        $text = $this->header . $this->args->options;
        if ( $withContents )
            $text .= implode( "" , $this->texts );
        $text = str_replace( " " , "" , $text );
        $text = str_replace( "\n" , "" , $text );
        $text = str_replace( "\r" , "" , $text );
        $text = str_replace( "\t" , "" , $text );
        return hash( "crc32b" , $text );
    }
}
