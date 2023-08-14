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

$run = new RevcheckRun( 'en' , $argv[1] , true );

foreach( $run->targetFiles->list as $file )
{
    if ( $file->revtag == null ) continue;

    //print $file->file . " : " . $file->revtag->maintainer . " : " . $file->revtag->credits . "\n";
    //continue;

    if ( $file->revtag->errors != "" )
    {
        //print $file->file . "\n";
        print $file->revtag->errors ; "\n";
    }
}
