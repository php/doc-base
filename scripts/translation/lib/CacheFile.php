<?php

require_once __DIR__ . '/all.php';

/**
 * Class to handle data persistence
 */

class CacheFile
{
    const CACHE_DIR = __DIR__ . '/../.cache';

    private string $filename;

    function __construct( string $file )
    {
        $this->filename = CacheFile::prepareFilename( "" , $file , true );
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