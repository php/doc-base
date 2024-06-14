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
        $hash = "";
        $date = "";
        $skip = false;
        while ( ( $line = fgets( $fp ) ) !== false )
        {
            // new commit block
            if ( substr( $line , 0 , 7 ) == "commit " )
            {
                $hash = trim( substr( $line , 7 ) );
                $date = "";
                $skip = false;
                continue;
            }
            // datetime of commit
            if ( strpos( $line , 'Date:' ) === 0 )
            {
                $line = trim( substr( $line , 5 ) );
                $date = strtotime( $line );
                continue;
            }
            // other headers
            if ( strpos( $line , ': ' ) > 0 )
                continue;
            // empty lines
            if ( trim( $line ) == "" )
                continue;
            // commit message
            if ( str_starts_with( $line , '    ' ) )
            {
                // commits with this mark are ignored
                if ( stristr( $line, '[skip-revcheck]' ) !== false )
                    $skip = true;
                continue;
            }
            // otherwise, a filename
            $filename = trim( $line );
            $info = $list->get( $filename );

            // untracked file (deleted, renamed)
            if ( $info == null )
                continue;

            // the head commit
            if ( $info->head == "" )
            {
                $info->head = $hash;
                $info->date = $date;
            }

            // after, only tracks non skipped commits
            if ( $skip )
                continue;

            // the diff commit
            if ( $info->diff == "" )
            {
                $info->diff = $hash;
                $info->date = $date;
            }
        }

        pclose( $fp );
        chdir( $cwd );
    }
}
