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
 *  | Description: Intercept and process $argv parameters.                 |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class OutputIgnoreArgv
{
    public string $command = "";
    public string $options = "";
    public bool $showIgnore = true;

    function __construct( array & $argv )
    {
        $this->command = $argv[0];

        foreach( $argv as $key => $arg )
        {
            if ( str_starts_with( $arg , "--add-ignore=" ) )
            {
                $file = OutputIgnoreArgv::cacheFile();
                $list = $file->load( array() );
                $line = substr( $arg , 13 );
                if ( ! in_array( $line , $list ) )
                {
                    $list[] = $line;
                    $file->save( $list );
                }
                exit;
            }

            if ( str_starts_with( $arg , "--del-ignore=" ) )
            {
                $file = OutputIgnoreArgv::cacheFile();
                $list = $file->load( array() );
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

        $copy = $argv;
        array_shift( $copy );
        $this->options = implode( " " , $copy );
    }

    public static function cacheFile()
    {
        return new CacheFile( getcwd() . "/.qaxml.ignore" );
    }

    function pushAddIgnore( OutputIgnoreBuffer $output, string $mark )
    {
        $output->addFooter( "  php {$this->command} --add-ignore=$mark\n" );
    }

    function pushDelIgnore( OutputIgnoreBuffer $output, string $mark )
    {
        $output->addFooter( "  php {$this->command} --del-ignore=$mark\n" );
    }
}
