<?php

require_once __DIR__ . '/lib/require.php';

if ( count( $argv ) < 2 )
{
    fwrite( STDERR , "Missing paramater.\n" );
    fwrite( STDERR , "Usage:\n" );
    fwrite( STDERR , "    {$argv[0]} [lang_dir]:\n" );
    return;
}

new RevcheckRun( 'en' , $argv[1] , true );