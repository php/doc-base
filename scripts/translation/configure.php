<?php

/**
 * configure.php -- Run revcheck and generate cached data
 */

require_once __DIR__ . '/lib/all.php';

if ( count( $argv ) < 2 )
{
    fwrite( STDERR , "  Missing paramater. Usage:\n" );
    fwrite( STDERR , "    {$argv[0]} [lang_dir]:\n" );
    return;
}

new RevcheckRun( 'en' , $argv[1] , true );
