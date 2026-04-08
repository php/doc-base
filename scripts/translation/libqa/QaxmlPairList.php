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

Generates and caches the list of file path pairs used QAXML tools.    */

require_once __DIR__ . '/all.php';

class QaxmlPairList
{
    static function load( ?string $lang = null , array $filterFiles = [] )
    {
        if ( $lang === null )
        {
            $file = __DIR__ . "/../../../temp/lang";
            if ( ! file_exists( $file ) )
            {
                fwrite( STDERR , "Language to process. Run 'doc-base/configure.php' or use '--lang='.\n" );
                exit();
            }
            $lang = trim( file_get_contents( $file ) );
        }

        $sourceDir = 'en';
        $targetDir = $lang;

        if ( count( $filterFiles ) > 0 )
        {
            $ret = [];

            foreach ( $filterFiles as $file )
            {
                if ( ! file_exists( "$sourceDir/$file" ) )
                {
                    fwrite( STDERR , "File not found on source side, ignored: $sourceDir/$file\n" );
                    continue;
                }
                if ( ! file_exists( "$targetDir/$file" ) )
                    continue;

                $item = new QaxmlPairItem();
                $item->sourceDir = $sourceDir;
                $item->targetDir = $targetDir;
                $item->file = $file;
                $ret[] = $item;
            }

            if ( $ret === [] )
                throw new Exception( "No matching files found." );

            return $ret;
        }

        $cacheFilename = __DIR__ . "/../../../temp/qaxml.pairs.$lang.gz";

        if ( file_exists( $cacheFilename ) )
            return unserialize( gzdecode( file_get_contents( $cacheFilename ) ) );

        require_once __DIR__ . '/../lib/all.php';

        $revFiles = new RevcheckFileList( $sourceDir );
        $ret = [];

        foreach( $revFiles->iterator() as $file )
        {
            if ( ! file_exists( "$targetDir/{$file->file}" ) )
                continue;

            $item = new QaxmlPairItem();
            $item->sourceDir = $sourceDir;
            $item->targetDir = $targetDir;
            $item->file = $file->file;
            $ret[] = $item;
        }

        if ( $ret === [] )
            throw new Exception( "No files found. Called from wrong directory?" );

        $contents = gzencode( serialize( $ret ) );
        file_put_contents( $cacheFilename , $contents );

        return $ret;
    }
}
