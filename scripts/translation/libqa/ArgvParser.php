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

This class coordinates and centrailzes control for $argv command line
parameters, used between vairous classes.                             */

class ArgvParser
{
    private array $argv;
    private array $used;

    public function __construct( array $argv )
    {
        $this->argv = $argv;
        $this->used = [];
        $this->used = array_fill(0, count($argv), false);
    }

    public function at( int $pos ) : string
    {
        $this->used[ $pos ] = true;
        return $this->argv[ $pos ];
    }

    public function consume( string $equals = null , string $prefix = null , int $position = -1 ) : string|null
    {
        $args = $this->argv;
        foreach ( $args as $pos => $arg )
        {

            if ( $arg == null )
                continue;

            $foundByEquals = $equals != null && $arg == $equals;
            $foundByPrefix = $prefix != null && str_starts_with( $arg , $prefix );
            $foundByPosition = $position == $pos;

            if ( $foundByEquals || $foundByPrefix || $foundByPosition )
            {
                $this->argv[ $pos ] = null;
                $this->used[ $pos ] = true;

                return $arg;
            }
        }

        return null;
    }

    public function complete() : void
    {
        for ( $pos = 0 ; $pos < count( $this->argv ) ; $pos++ )
            if ( $this->used[ $pos ] == false )
                fwrite( STDERR , "Unknown argument: {$this->argv[$pos]}\n\n" );
    }

    public function residual() : array
    {
        $ret = [];
        foreach ( $this->argv as $arg )
            if ( $arg != null )
                $ret[] = $arg;
        return $ret;
    }
}
