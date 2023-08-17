<?php

/**
 * qarvt.php -- Check format for revtags and credits on XML comments
 */

require_once __DIR__ . '/lib/all.php';

$langDir = "";

switch ( count( $argv ) )
{
    case 1:
        break;
    case 2:
        $langDir = $argv[1];
        break;
    default:
        print_usage_exit();
        return;
}

if ( $langDir == "" )
{
    $qalist = QaFileInfo::cacheLoad();
    if ( count( $qalist ) > 0 )
    {
        foreach( $qalist as $qa )
        {
            $langDir = $qa->targetDir;
            break;
        }
    }
    else
        print_usage_exit();
}

$list = new RevcheckFileList( $langDir );

foreach( $list->list as $item )
{
    $file = $langDir . '/' . $item->file;
    $revt = RevtagParser::parseFile( $file );

    if ( count( $revt->errors ) == 0 )
        continue;

    print "qarvt: $file\n";
    foreach( $revt->errors as $error )
        print " $error\n";
    print "\n";
}

function print_usage_exit()
{
    fwrite( STDERR , "  Wrong paramater count. Usage:\n" );
    fwrite( STDERR , "    {$argv[0]} [lang_dir]:\n" );
    exit;
}
