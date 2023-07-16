<?php

require_once __DIR__ . '/require.php';

/**
 * Minimal input for QA tools
 */

class QaFileInfo
{
    public string $sourceHash;
    public string $targetHash;
    public string $file;
    public int    $days;

    function __construct( string $sourceHash , string $targetHash , string $file , int $days )
    {
        $this->sourceHash = $sourceHash;
        $this->targetHash = $targetHash;
        $this->file = $file;
        $this->days = $days;
    }

    public static function cacheLoad() :array
    {
        return CacheUtil::load( "" , "QaFileInfo.phps" , $itens );
    }

    public static function cacheSave( array $itens )
    {
        // PHP serialize()
        CacheUtil::save( "" , "QaFileInfo.phps" , $itens );

        // CSV
        $filename = CacheUtil::prepareFilename( "" , "QaFileInfo.csv" , true );
        $fp = fopen( $filename , 'w' );
        foreach( $itens as $item )
        {
            $line = array( $item->sourceHash , $item->targetHash , $item->file , $item->days );
            fputcsv( $fp , $line );
        }
        fclose($fp);
    }
}