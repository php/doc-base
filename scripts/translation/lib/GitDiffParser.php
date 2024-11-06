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
 *  | Description: Parse `git diff` to complement file state.              |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class GitDiffParser
{
    public static function parseAddsDels( string $chdir , RevcheckDataFile $file )
    {
        $cwd = getcwd();
        chdir( $chdir );

        $hash = $file->hashRvtg;
        $name = $file->path == "" ? $file->name : $file->path . "/" . $file->name;

        $hash = escapeshellarg( $hash );
        $name = escapeshellarg( $name );

        $output = `git diff --numstat $hash -- $name`;
        if ( $output )
        {
            preg_match( '/(\d+)\s+(\d+)/' , $output , $matches );
            if ( $matches )
            {
                $file->adds = $matches[1];
                $file->dels = $matches[2];
            }
        }

        chdir( $cwd );
    }
}
