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
    private string $filename = ".qaxml.ignores";

    public ArgvParser $argv;
    public string $argvZero = "";
    public array  $argvRest = [];
    public bool $appendIgnoreCommands = true;

    public function __construct( ArgvParser $argv )
    {
        $this->argv = $argv;
        $this->argvZero = escapeshellarg( $argv->consume( position: 0 ) );
        $this->argvRest = $this->argv->residual();

        $item = $argv->consume( prefix: "--add-ignore=" );

        if ( $item != null )
        {
            $list = $this->loadIgnores();
            if ( ! in_array( $item , $list ) )
            {
                $list[] = $item;
                $this->saveIgnores( $list );
            }
            exit;
        }

        $item = $argv->consume( prefix: "--del-ignore=" );
        if ( $item != null )
        {
            $list = $this->loadIgnores();
            $dels = 0;
            while ( in_array( $item , $list ) )
            {
                $key = array_search( $item , $list );
                unset( $list[$key] );
                $dels++;
            }
            if ( $dels == 0 )
                print "Ignore mark not found.\n";
            else
                $this->saveIgnores( $list );
            exit;
        }

        if ( $argv->consume( "--disable-ignore" ) != null )
            $this->appendIgnoreCommands = false;
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

    public function shouldIgnore( OutputBuffer $output , string $hashFile , string $hashHeader , string $hashMatter )
    {
        $ret = false;

        $prefix = "{$hashFile}-{$hashHeader}-";
        $active = "{$hashFile}-{$hashHeader}-{$hashMatter}";
        $marks = $this->loadIgnores();

        // --add-ignore command

        if ( in_array( $active , $marks ) )
            $ret = true;
        else
            if ( $this->appendIgnoreCommands )
                $output->addFooter( "  php {$this->argvZero} --add-ignore=$active\n" );

        // --del-ignore command

        if ( $this->appendIgnoreCommands )
            foreach ( $marks as $mark )
                if ( str_starts_with( $mark , $prefix ) )
                    if ( $mark != $active )
                        $output->addFooter( "  php {$this->argvZero} --del-ignore=$mark\n" );

        return $ret;
    }
}
