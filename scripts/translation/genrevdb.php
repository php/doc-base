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
 *  | Description: Check format for revtags and credits on XML comments.   |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/lib/all.php';

if ( count( $argv ) < 3 || in_array( '--help' , $argv ) || in_array( '-h' , $argv ) )
{
    fwrite( STDERR , "\nUsage: {$argv[0]} [file.db] [lang1,langN]\n\n" );
    return;
}

$timeStart = new \DateTime;
$dbpath = $argv[1];
$langs = explode( ',' , $argv[2] );

consolelog( "Creating revdata database $dbpath for languages $argv[2]." );

$db = db_create( $dbpath );
foreach( $langs as $lang )
    generate( $db , $lang );

consolelog( "Revdata database $dbpath complete." );
exit;



function generate( SQLite3 $db , string $lang )
{
    $cwd = getcwd();
    if ( ! is_dir( $lang )  )
    {
        consolelog( "Error: '$cwd/$lang' doesn't exist. Skipped." );
        return;
    }
    if ( ! is_file( "$lang/translation.xml" ) )
    {
        consolelog( "Error: '$cwd/$lang' doesn't contains translation.xml. Skipped." );
        return;
    }

    try
    {
        consolelog( "Language $lang run" );

        $revcheck = new RevcheckRun( 'en' , $lang );
        $data = $revcheck->revData;

        $db->exec( 'BEGIN TRANSACTION' );

        db_insert( $db , "languages" , $data->lang , $data->intro );

        foreach( $data->translators as $translator )
            if ( $translator->nick != "" )
                db_insert( $db , "translators", $data->lang
                    , $translator->name
                    , $translator->nick
                    , $translator->email
                    , $translator->vcs
                    , $translator->countOk
                    , $translator->countOld
                    , $translator->countOther
                );

        foreach( $data->fileDetail as $file )
            if ( $translator->nick != "" )
                db_insert( $db , "files", $data->lang
                    , $file->path
                    , $file->name
                    , $file->size
                    , $file->days
                    , $file->adds
                    , $file->dels
                    , $file->status->value
                    , $file->maintainer
                    , $file->completion
                    , $file->hashLast
                    , $file->hashDiff
                    , $file->hashRvtg
                );

        $filesTotal = 0;
        foreach( $data->fileSummary as $count )
            $filesTotal += $count;
        $labels = $data->getSummaryLabels();
        foreach( $data->fileSummary as $status => $count )
            db_insert( $db , "summary", $data->lang
                , $status
                , $labels[ $status ]
                , $count
                , number_format( $count / $filesTotal * 100 , 2 ) . "%"
            );

        $db->exec( 'COMMIT TRANSACTION' );
        consolelog_timed( "Language $lang done" );
    }
    catch ( Exception $e )
    {
        $db->exec( 'ROLLBACK TRANSACTION' );
        consolelog( "Throw: " . $e->getMessage() );
        exit;
    }
}

function db_insert( SQLite3 $db , string $table , ... $values ) : void
{
    $dml = "INSERT INTO $table VALUES (";
    $cmm = "";
    foreach( $values as $v )
    {
        $dml .= "$cmm?";
        $cmm = ",";
    }
    $dml .= ");\n";

    $cmd = $db->prepare( $dml );
    if ( ! $cmd )
    {
        consolelog_error( "Error: Prepare failed." , $db );
        throw new \Exception;
    }

    $idx = 0;
    foreach( $values as $val )
    {
        $idx++;
        $cmd->bindValue( $idx , $val );
    }

    $sql = $cmd->getSQL( true );

    $res = $cmd->execute();
    if ( ! $res )
    {
        consolelog_error( "Error: '$sql'" , $db );
        throw new \Exception;
    }
}

function db_create( $path ) : SQLite3
{
    if ( is_file ( $path ) )
    {
        consolelog( "Previous database file found, deleting." );
        if ( ! @ unlink ( $path ) )
        {
            consolelog( "Error: Can't remove temporary database." );
            exit;
        }
    }

    $ddl = <<<SQL
CREATE TABLE languages (
    lang TEXT,
    intro TEXT,
    UNIQUE ( lang ) );

CREATE TABLE translators (
    lang  TEXT,
    name  TEXT,
    nick  TEXT,
    email TEXT,
    vcs   TEXT,
    countOk    INT,
    countOld   INT,
    countOther INT,
    UNIQUE ( lang , nick ) );

CREATE TABLE summary (
    lang TEXT,
    status TEXT,
    label TEXT,
    count INT,
    perct TEXT,
    UNIQUE ( lang , status ) );

CREATE TABLE files (
    lang TEXT,
    path TEXT,
    name TEXT,
    size INT,
    days INT,
    adds INT,
    dels INT,
    status TEXT,
    maintainer TEXT,
    completion TEXT,
    hashLast TEXT,
    hashDiff TEXT,
    hashRvtg TEXT,
    UNIQUE ( lang , path , name ) );
SQL;

    try
    {
        $db = new SQLite3( $path );
        if ( ! $db->exec( $ddl ) )
        {
            consolelog_error( "Error: Database creation failed." , $db );
            exit;
        }
        return $db;
    }
    catch ( Exception $e )
    {
        consolelog( "Throw: " . $e->getMessage() );
        exit;
    }
}

function consolelog( $message ) : void
{
    $time = (new \DateTime())->format('Y-m-d H:i');
    echo "[$time] $message\n";
}

function consolelog_timed( $message ) : void
{
    static $lastMark = null;
    if ( $lastMark == null )
    {
        global $timeStart;
        $lastMark = $timeStart;
    }
    $seconds = (new \DateTime)->getTimestamp() - $lastMark->getTimestamp();
    $lastMark = new \DateTime;
    $time = $lastMark->format('Y-m-d H:i');
    echo sprintf( "[%s] %s (elapsed %.02fs)\n", $time, $message, $seconds );
}

function consolelog_error( string $message , SQLite3 $db ) : void
{
    consolelog( $message );
    consolelog( 'SQLite3: (' . $db->lastErrorCode() . ') ' . $db->lastErrorMsg() );
}
