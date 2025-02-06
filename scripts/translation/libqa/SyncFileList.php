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
        $cache = __DIR__ . "/../../../temp/$lang.oklist";

        if ( file_exists( $cache ) )
        {
            $data = file_get_contents( $cache );
            return unserialize( gzdecode( $data ) );
        }

        require_once __DIR__ . '/../lib/all.php';

        $revcheck = new RevcheckRun( 'en' , $lang );
        $revdata  = $revcheck->revData;
        $list = [];

        foreach( $revdata->fileDetail as $file )
        {
            if ( $file->status != RevcheckStatus::TranslatedOk )
                continue;

            $item = new SyncFileItem();
            $item->sourceDir = $revcheck->sourceDir;
            $item->targetDir = $revcheck->targetDir;
            $item->file = $file->path . '/' . $file->name;
            $list[] = $item;
        }

        $contents = gzencode( serialize( $list ) );
        file_put_contents( $cache , $contents );

        return $list;
    }
}
