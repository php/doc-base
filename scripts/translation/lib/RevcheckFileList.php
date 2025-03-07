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
 *  | Description: General file transversal, ordered file listing.         |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class RevcheckFileList
{
    private $list = [];

    function __construct( $lang )
    {
        $this->loadTree( $lang );
    }

    function get( $file ): RevcheckFileItem|null
    {
        return $this->list[ $file ] ?? null;
    }

    function iterator(): Iterator
    {
        return new ArrayIterator( $this->list );
    }

    function loadTree( $lang )
    {
        $dir = new \DirectoryIterator( $lang );
        if ( $dir === false )
            die( "$lang is not a directory.\n" );
        $cwd = getcwd();
        chdir( $lang );
        $this->loadTreeRecurse( $lang , "" );
        chdir( $cwd );
    }

    function loadTreeRecurse( $lang , $path )
    {
        $todoDirs = [];
        $dir = new DirectoryIterator( $path == "" ? "." : $path );
        if ( $dir === false )
            die( "$path is not a directory.\n" );

        foreach( $dir as $entry )
        {
            $name = $entry->getFilename();
            $key = ltrim( $path . '/' . $name , '/' );
            if ( $name[0] == '.' )
                continue;
            if ( $entry->isDir() )
            {
                $todoDirs[] = $key;
                continue;
            }

            if ( RevcheckIgnore::ignore( $key ) )
                continue;
            $file = new RevcheckFileItem( $key , $entry->getSize() );
            $this->list[ $key ] = $file;
        }

        sort( $todoDirs );
        foreach( $todoDirs as $path )
            $this->loadTreeRecurse( $lang , $path );
    }
}
