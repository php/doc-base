<?php

require_once __DIR__ . '/all.php';

/**
 * General file transversal, ordered file listing
 */

class RevcheckFileList
{
    var $list = array();

    function __construct( $lang )
    {
        $this->loadTree( $lang );
    }

    function get( $file ): RevcheckFileInfo|null
    {
        return $this->list[ $file ] ?? null;
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
            $file = new RevcheckFileInfo( $key , $entry->getSize() );
            $this->list[ $key ] = $file;
        }

        sort( $todoDirs );
        foreach( $todoDirs as $path )
            $this->loadTreeRecurse( $lang , $path );
    }
}