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

This class process commands for ignoring outputs, and complement non
ignored outputs with these commands.                                  */

class OutputIgnore
{
    private bool   $appendIgnores = true;
    private string $command;

    function __construct( array & $argv )
    {
        $this->command = escapeshellarg( $argv[0] );

        foreach( $argv as $key => $arg )
        {
            if ( str_starts_with( $arg , "--add-ignore=" ) )
            {
                $list = $this->loadCache();
                $line = substr( $arg , 13 );
                if ( ! in_array( $line , $list ) )
                {
                    $list[] = $line;
                    $this->saveCache( $list );
                    $file->save( $list );
                }
                exit;
            }

            if ( str_starts_with( $arg , "--del-ignore=" ) )
            {
                $list = $this->loadCache();
                $line = substr( $arg , 13 );
                $dels = 0;
                while ( in_array( $line , $list ) )
                {
                    $key = array_search( $line , $list );
                    unset( $list[$key] );
                    $dels++;
                }
                if ( $dels == 0 )
                    print "Ignore mark not found.\n";
                else
                    $file->save( $list );
                exit;
            }

            if ( $arg == "--disable-ignore" )
            {
                $this->showIgnore = false;
                unset( $argv[$key] );
            }
        }
    }

    public function shouldIgnore( OutputBuffer $output , string $filename , string $hashHeader , string $hashMatter )
    {
        $ret = false;

        $prefix = "{$filename}:{$hashHeader}:";
        $ignore = "{$filename}:{$hashHeader}:{$hashMatter}";
        $addign = escapeshellarg( "--add-ignore=$ignore" );
        $delign = escapeshellarg( "--del-ignore=$ignore" );
        $list = $this->loadCache();

        // --add-ignore

        if ( in_array( $ignore , $list ) )
            $ret = true;
        else
            if ( $this->showIgnore )
                $output->addFooter( "  php {$this->command} $addign\n" );

        // Remove valid ignores before listing outdated ones

        while ( in_array( $ignore , $marks ) )
        {
            $key = array_search( $ignore , $marks );
            unset( $marks[$key] );
        }

        // --del-ignore

        if ( $this->showIgnore )
            foreach ( $list as $mark )
                if ( $mark != null )
                    if ( str_starts_with( $mark , $prefix ) )
                        $output->addFooter( "  php {$this->command} $delign\n" );

        return $ret;
    }
}
