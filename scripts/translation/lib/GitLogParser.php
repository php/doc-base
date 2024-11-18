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
 *  | Description: Parse `git log` to complement file state.               |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class GitLogParser
{
    static function parseInto( string $lang , RevcheckFileList & $list )
    {
        $cwd = getcwd();
        chdir( $lang );
        $fp = popen( "git log --name-only" , "r" );
        chdir( $cwd );

        $hash = "";
        $date = "";
        $skip = false;
        $mcnt = 0;

        while ( ( $line = fgets( $fp ) ) !== false )
        {
            // new commit block
            if ( substr( $line , 0 , 7 ) == "commit " )
            {
                $hash = trim( substr( $line , 7 ) );
                $date = "";
                $skip = false;
                $mcnt = 0;
                continue;
            }
            // datetime of commit
            if ( strpos( $line , 'Date:' ) === 0 )
            {
                $line = trim( substr( $line , 5 ) );
                $date = strtotime( $line );
                continue;
            }
            // empty lines
            if ( trim( $line ) == "" )
                continue;
            // commit message
            if ( str_starts_with( $line , '    ' ) )
            {
                if ( LOOSE_SKIP_REVCHECK ) // See below, and https://github.com/php/doc-base/pull/132
                {
                    // commits with [skip-revcheck] anywhere commit message flags skip
                    if ( str_contains( $line, '[skip-revcheck]' ) )
                        $skip = true;
                }
                else
                {
                    $mcnt++;
                    // [skip-revcheck] at start of first line of commit message flags a skip
                    if ( $mcnt == 1 && str_starts_with( trim( $line ) , '[skip-revcheck]' ) )
                        $skip = true;
                }
                continue;
            }
            // other headers
            if ( strpos( $line , ': ' ) > 0 )
                continue;

            // otherwise, a filename
            $filename = trim( $line );
            $info = $list->get( $filename );

            // untracked file (deleted, renamed)
            if ( $info == null )
                continue;

            $info->addGitLogData( $hash , $date , $skip );
        }

        pclose( $fp );
    }
}
