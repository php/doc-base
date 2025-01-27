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
    public array $residualArgv;

    private bool   $appendIgnores = true;
    private bool   $showIgnore = true;
    private string $filename = ".syncxml.ignores";
    private string $argv0 = "";

    function __construct( array & $argv )
    {
        $this->argv0 = escapeshellarg( $argv[0] );

        foreach( $argv as $key => $arg )
        {
            if ( str_starts_with( $arg , "--add-ignore=" ) )
            {
                $list = $this->loadIgnores();
                $line = substr( $arg , 13 );
                if ( ! in_array( $line , $list ) )
                {
                    $list[] = $line;
                    $this->saveIgnores( $list );
                    $file->save( $list );
                }
                exit;
            }

            if ( str_starts_with( $arg , "--del-ignore=" ) )
            {
                $list = $this->loadIgnores();
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
                    $file->saveIgnores( $list );
                exit;
            }

            if ( $arg == "--disable-ignore" )
            {
                $this->showIgnore = false;
                unset( $argv[$key] );
            }
        }

        $this->residualArgv = $argv;
    }

    private function loadIgnores()
    {
        if ( ! file_exists( $this->filename ) )
            return [];
        $data = file_get_contents( $this->filename );
        return unserialize( gzdecode( $data ) );
    }

    public function saveIgnores( $data )
    {
        $contents = gzencode( serialize( $data ) );
        file_put_contents( $this->filename , $contents );
    }

    public function shouldIgnore( OutputBuffer $output , string $filename , string $hashHeader , string $hashMatter )
    {
        $ret = false;

        $prefix = "{$filename}:{$hashHeader}:";
        $ignore = "{$filename}:{$hashHeader}:{$hashMatter}";
        $addign = escapeshellarg( "--add-ignore=$ignore" );
        $delign = escapeshellarg( "--del-ignore=$ignore" );

        $marks = $this->loadIgnores();

        // --add-ignore

        if ( in_array( $ignore , $marks ) )
            $ret = true;
        else
            if ( $this->showIgnore )
                $output->addFooter( "  php {$this->argv0} $addign\n" );

        // Remove valid ignores, leaves outdated ones for listing

        while ( in_array( $ignore , $marks ) )
        {
            $key = array_search( $ignore , $marks );
            unset( $marks[$key] );
        }

        // --del-ignore

        if ( $this->showIgnore )
            foreach ( $marks as $mark )
                if ( $mark != null )
                    if ( str_starts_with( $mark , $prefix ) )
                        $output->addFooter( "  php {$this->argv0} $delign\n" );

        return $ret;
    }
}
