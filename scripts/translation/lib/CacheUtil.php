<?php

require_once __DIR__ . '/require.php';

/**
 * Common functions do load and save to cache
 */

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