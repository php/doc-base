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
 *  | Description: Class to handle data persistence.                       |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class CacheFile
{
    const CACHE_DIR = __DIR__ . '/../.cache';

    private string $filename;

    function __construct( string $file )
    {
        $this->filename = CacheFile::prepareFilename( $file , true );
    }

    public function load( mixed $init = null )
    {
        if ( file_exists( $this->filename ) == false )
            return $init;
        $data = file_get_contents( $this->filename );
        return unserialize( gzdecode( $data ) );
    }

    public function save( $data )
    {
        $contents = gzencode( serialize( $data ) );
        file_put_contents( $this->filename , $contents );
    }

    public static function prepareFilename( string $file , bool $createCacheDirs = false )
    {
        if ( str_starts_with( $file , '/' ) )
            return $file;
        $outPath = CacheUtil::CACHE_DIR . '/' . dirname( $file );
        $outFile = rtrim( $outPath , '/' ) . '/' . $file;
        if ( $createCacheDirs && file_exists( $outPath ) == false )
            mkdir( $outPath , 0777 , true );
        return $outFile;
    }
}
