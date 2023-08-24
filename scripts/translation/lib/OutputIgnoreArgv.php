<?php

require_once __DIR__ . '/all.php';

/**
 * Intercept and process $argv parameters
 */

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
        return new CacheFile( getcwd() . "/.qa.ignore" );
    }

    function pushAddIgnore( OutputIgnoreBuffer $output, string $mark )
    {
        $output->add( "  php {$this->command} --add-ignore=$mark\n" );
    }

    function pushDelIgnore( OutputIgnoreBuffer $output, string $mark )
    {
        $output->add( "  php {$this->command} --del-ignore=$mark\n" );
    }
}