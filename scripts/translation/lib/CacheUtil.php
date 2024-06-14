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
 *  | Description: Common functions do load and save to cache files.       |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class CacheUtil
{
    const CACHE_DIR = __DIR__ . '/../.cache';

    public static function load( string $path , string $file )
    {
        $filename = CacheUtil::prepareFilename( $path , $file , true );
        if ( file_exists( $filename ) == false )
            return null;
        $data = file_get_contents( $filename );
        return unserialize( $data );
    }

    public static function save( string $path , string $file , $data )
    {
        $outFile = CacheUtil::prepareFilename( $path , $file , true );
        $contents = serialize( $data );
        file_put_contents( $outFile , $contents );
    }

    public static function prepareFilename( string $path , string $file , bool $createDirs = false )
    {
        $baseDir = CacheUtil::CACHE_DIR;
        $outPath = rtrim( $baseDir , '/' ) . '/' . $path;
        $outFile = rtrim( $outPath , '/' ) . '/' . $file;
        if ( $createDirs && file_exists( $outPath ) == false )
            mkdir( $outPath , 0777 , true );
        return $outFile;
    }
}
