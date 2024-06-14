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
 *  | Description: Check format for revtags and credits on XML comments.   |
 *  +----------------------------------------------------------------------+
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
        print_usage_exit($argv[0]);
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
        print_usage_exit($argv[0]);
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

function print_usage_exit($cmd)
{
    fwrite( STDERR , "  Wrong paramater count. Usage:\n" );
    fwrite( STDERR , "    {$cmd} [lang_dir]:\n" );
    exit;
}
