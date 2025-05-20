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
 *  | Description: Common data for revcheck and QA tools.                  |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class QaFileInfo
{
    public string $sourceHash;
    public string $targetHash;
    public string $sourceDir;
    public string $targetDir;
    public string $file;
    public int    $days;

    function __construct( string $sourceHash , string $targetHash , string $sourceDir , string  $targetDir , string $file , int $days )
    {
        $this->sourceHash = $sourceHash;
        $this->targetHash = $targetHash;
        $this->sourceDir = $sourceDir;
        $this->targetDir = $targetDir;
        $this->file = $file;
        $this->days = $days;
    }

    public static function cacheLoad() :array
    {
        return CacheUtil::load( "" , "QaFileInfo.phps" );
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
            $line = array( $item->sourceHash , $item->targetHash , $item->sourceDir , $item->targetDir , $item->file , $item->days );
            fputcsv( $fp , $line, escape: "" );
        }
        fclose($fp);
    }
}
