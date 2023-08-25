<?php

require_once __DIR__ . '/all.php';

/**
 * Cache the output and shows if if not marked to ignore
 */

class OutputIgnoreBuffer
{
    public int $printCount = 0;

    private string $header = "";
    private string $filename = "";
    private array $texts = array();

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

        $head = $this->filename . ':' . $this->hash( false ) . ':';
        $mark = $head . $this->hash( true );
        $marks = OutputIgnoreArgv::cacheFile()->load( array() );

        if ( $this->args->showIgnore )
        {
            if ( in_array( $mark , $marks ) )
                $this->texts = array();
            else
                $this->args->pushAddIgnore( $this , $mark );

            // old marks
            while ( in_array( $mark , $marks ) )
            {
                $key = array_search( $mark , $marks );
                unset( $marks[$key] );
            }
            foreach ( $marks as $mark )
                if ( $mark != null )
                    if ( str_starts_with( $mark , $head ) )
                        $this->args->pushDelIgnore( $this , $mark );

        }

        $this->addLine( "\n" );

        if ( count( $this->texts ) == 0 )
            return;

        print $this->header;
        foreach( $this->texts as $text )
            print $text;
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