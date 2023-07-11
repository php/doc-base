<?php

require_once __DIR__ . '/require_all.php';

class RevcheckFileList
{
    private $list;

    function __construct( $lang )
    {
        $this->loadTree( $lang );
    }

    function loadTree( $lang )
    {
        $dir = new \DirectoryIterator( $lang );
        if ( $dir === false )
            die( "$lang is not a directory.\n" );
        $cwd = getcwd();
        chdir( $lang );
        $this->loadTreeRecurse( $lang , "" , $ret );
        chdir( $cwd );
        return $ret;
    }

    function loadTreeRecurse( $lang , $path , & $output )
    {
        $todoDirs = [];
        $dir = new DirectoryIterator( $path == "" ? "." : $path );
        if ( $dir === false )
            die( "$path is not a directory.\n" );

        foreach( $dir as $entry )
        {
            $name = $entry->getFilename();
            if ( $name[0] == '.' )
                continue;
            if ( $entry->isDir() )
            {
                $todoDirs[] = ltrim( $path . '/' . $name , '/' );
                continue;
            }

            $file = new RevcheckFileInfo( $path , $name , $entry->getSize() );
            if ( RevcheckIgnore::ignore( $file->key ) )
                continue;
            $output[ $file->key ] = $file;
        }

        sort( $todoDirs );
        foreach( $todoDirs as $path )
            $this->loadTreeRecurse( $lang , $path , $output );
    }
}