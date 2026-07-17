<?php
# +----------------------------------------------------------------------+
# | Copyright (c) 1997-2024 The PHP Group                                |
# +----------------------------------------------------------------------+
# | This source file is subject to version 3.01 of the PHP license,      |
# | that is bundled with this package in the file LICENSE, and is        |
# | available through the world-wide-web at the following url:           |
# | https://www.php.net/license/3_01.txt.                                |
# | If you did not receive a copy of the PHP license and are unable to   |
# | obtain it through the world-wide-web, please send a note to          |
# | license@php.net, so we can mail you a copy immediately.              |
# +----------------------------------------------------------------------+
# | Authors:     André L F S Bacci <ae php.net>                          |
# +----------------------------------------------------------------------+
# | Description: Common functions that interact with git command line.   |
# +----------------------------------------------------------------------+

require_once __DIR__ . '/all.php';

class GitSlowUtils
{
    public static function checkDiffOnlyWsChange( string $gdir , RevcheckDataFile $file ) : bool
    {
        $hash = $file->hashRvtg;
        $flnm = $file->path == "" ? $file->name : $file->path . "/" . $file->name;

        $gdir = escapeshellarg( $gdir );
        $flnm = escapeshellarg( $flnm );
        $hash = escapeshellarg( $hash );

        $func = '[' . __CLASS__ . ':' . __FUNCTION__ . ']';

        // Fast path

        // The git -b option is a bit misleading. It will ignore ws change
        // on existing ws runs, but will report insertion or remotion of
        // ws runs. This suffices for detecting significant ws changes and
        // also ignoring insignificant ws changes in most cases we are
        // interessed.

        $output = shell_exec("git -C $gdir diff -b $hash -- $flnm");
        $onlyws = $output == "";

        // Slow path

        if ( $onlyws )
        {
            $prev = shell_exec("git -C $gdir show $hash:$flnm )");
            $next = shell_exec("git -C $gdir show HEAD:$flnm )");

            if ( $prev == "" || $next == "" )
            {
                fprintf( STDERR , "$func Failed to read file contents.\n" );
                return $onlyws;
            }

            $prev = GitUtils::discardPrefixSuffixEmptyWs( $prev );
            $next = GitUtils::discardPrefixSuffixEmptyWs( $next );

            if ( $prev != $next )
            {
                // Not really an error, but a theory. Report this bug/issue
                // to start a discussion if this ws change must be ignored
                // or tracked.

                fprintf( STDERR , "$func Debug: Fast and slow path differ.\n" );
                return false;
            }
        }

        return $onlyws;
    }

    private static function discardPrefixSuffixEmptyWs( string $text ) : string
    {
        $lines = explode( "\n" , $text );
        $trimLines = [];
        foreach ( $lines as $line )
            $trimLines[] = trim( $line );
        return implode( "" , $trimLines );
    }

    public static function parseAddsDels( string $gdir , RevcheckDataFile $file )
    {
        $hash = $file->hashRvtg;
        $name = $file->path == "" ? $file->name : $file->path . "/" . $file->name;

        $gdir = escapeshellarg( $gdir );
        $hash = escapeshellarg( $hash );
        $name = escapeshellarg( $name );

        $output = shell_exec("git -C $gdir diff --numstat $hash -- $name");
        if ( $output )
        {
            preg_match( '/(\d+)\s+(\d+)/' , $output , $matches );
            if ( $matches )
            {
                $file->adds = $matches[1];
                $file->dels = $matches[2];
            }
        }
    }
}
