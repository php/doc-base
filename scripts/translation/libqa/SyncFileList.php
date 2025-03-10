<?php /*
+----------------------------------------------------------------------+
| Copyright (c) 1997-2025 The PHP Group                                |
+----------------------------------------------------------------------+
| This source file is subject to version 3.01 of the PHP license,      |
| that is bundled with this package in the file LICENSE, and is        |
| available through the world-wide-web at the following url:           |
| https://www.php.net/license/3_01.txt.                                |
| If you did not receive a copy of the PHP license and are unable to   |
| obtain it through the world-wide-web, please send a note to          |
| license@php.net, so we can mail you a copy immediately.              |
+----------------------------------------------------------------------+
| Authors:     André L F S Bacci <ae php.net>                          |
+----------------------------------------------------------------------+

# Description

Generates (and caches) the list of files with TranslatedOk status.    */

require_once __DIR__ . '/all.php';

class SyncFileList
{
    static function load()
    {
        $file = __DIR__ . "/../../../temp/lang";
        if ( ! file_exists( $file ) )
        {
            fwrite( STDERR , "Language file not found, run 'doc-base/configure.php'.\n" );
            exit();
        }

        $lang = trim( file_get_contents( $file ) );
        $cacheFilename = __DIR__ . "/../../../temp/qaxml.files.$lang";

        if ( file_exists( $cacheFilename ) )
        {
            $data = file_get_contents( $cacheFilename );
            return unserialize( gzdecode( $data ) );
        }

        $sourceDir = 'en';
        $targetDir = $lang;

        require_once __DIR__ . '/../lib/all.php';

        $files = new RevcheckFileList( $sourceDir );
        $syncFileItems = [];

        foreach( $files->iterator() as $file )
        {
            if ( ! file_exists( "$targetDir/{$file->file}" ) )
                continue;

            $item = new SyncFileItem();
            $item->sourceDir = $sourceDir;
            $item->targetDir = $targetDir;
            $item->file = $file->file;
            $ret[] = $item;
        }

        if ( count( $ret ) == 0 )
            throw new Exception( "No files found. Called from wrong directory?" );

        $contents = gzencode( serialize( $ret ) );
        file_put_contents( $cacheFilename , $contents );

        return $ret;
    }
}
