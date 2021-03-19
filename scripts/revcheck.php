#!/usr/bin/php -q
<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 4                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2011 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.0 of the PHP license,       |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_0.txt.                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors:    Thomas Sch�fbeck <tom@php.net>                           |
  |             Gabor Hojtsy <goba@php.net>                              |
  |             Mark Kronsbein <mk@php.net>                              |
  |             Jan Fabry <cheezy@php.net>                               |
  |             André L F S Bacci <ae@php.net>                           |
  +----------------------------------------------------------------------+
*/

if ( $argc != 2 )
{
    print <<<USAGE

  Check the revision of translated files against the actual english XML files
  and print statistics.

  Usage:
    {$argv[0]} [translation]

  [translation] must be a valid git checkout directory of a translation.

  Read more about revision comments and related functionality in the
  PHP Documentation Howto: http://doc.php.net/tutorial/


USAGE;
    exit;
}

// Initialization

set_time_limit( 0 );

$root = getcwd();
$lang = $argv[1];

$gitData = []; // filename lang hash,date

$enFiles = populateFileTree( 'en' );
$trFiles = populateFileTree( $lang );
captureGitValues( 'en'  , $gitData );
captureGitValues( $lang , $gitData );

computeSyncStatus( $enFiles , $trFiles , $gitData , $lang );

print_html_all( $enFiles , $trFiles , $lang );

// Model

class FileStatusEnum
{
    const Untranslated      = 'Untranslated';
    const RevTagProblem     = 'RevTagProblem';
    const TranslatedWip     = 'TranslatedWip';
    const TranslatedOk      = 'TranslatedOk';
    const TranslatedOld     = 'TranslatedOld';
    const TranslatedCritial = 'TranslatedCritial';
    const ExistsInEnTree    = 'ExistsInEnTree';
}

class FileStatusInfo
{
    public $path;
    public $name;
    public $size;
    public $hash;
    public $date;
    public $syncStatus;
    public $maintainer;
    public $completion;
    public $credits;

    public function getKey()
    {
        return trim( $this->path . '/' . $this->name , '/' );
    }
}

function populateFileTree( $lang )
{
    $dir = new \DirectoryIterator( $lang );
    if ( $dir === false )
    {
        print "$lang is not a directory.\n";
        exit;
    }
    $cwd = getcwd();
    $ret = array();
    chdir( $lang );
    populateFileTreeRecurse( $lang , "." , $ret );
    chdir( $cwd );
    return $ret;
}

function populateFileTreeRecurse( $lang , $path , & $output )
{
    $dir = new DirectoryIterator( $path );
    if ( $dir === false )
    {
        print "$path is not a directory.\n";
        exit;
    }
    $todoPaths = [];
    $trimPath = ltrim( $path , "./");
    foreach( $dir as $entry )
    {
        $filename = $entry->getFilename();
        if ( $filename[0] == '.' )
            continue;
        if ( substr( $filename , 0 , 9 ) == "entities." )
            continue;
        if ( $entry->isDir() )
        {
            $todoPaths[] = $path . '/' . $entry->getFilename();
            continue;
        }
        if ( $entry->isFile() )
        {
            $file = new FileStatusInfo;
            $file->path = $trimPath;
            $file->name = $filename;
            $file->size = filesize( $path . '/' . $filename );
            $file->syncStatus = null;
            if ( $lang != 'en' )
                parseRevisionTag( $entry->getPathname() , $file );
            $output[ $file->getKey() ] = $file;
        }
    }
    sort( $todoPaths );
    foreach( $todoPaths as $path )
        populateFileTreeRecurse( $lang , $path , $output );
}

function parseRevisionTag( $filename , FileStatusInfo $file )
{
    $fp = fopen( $filename , "r" );
    $contents = fread( $fp , 1024 );
    fclose( $fp );
    $regex = "/<!--\s*EN-Revision:\s*(.+)\s*Maintainer:\s*(.+)\s*Status:\s*(.+)\s*-->/U";
    $match = array();
    preg_match ( $regex , $contents , $match );
    if ( count( $match ) == 4 )
    {
        $file->hash = trim( $match[1] );
        $file->maintainer = trim( $match[2] );
        $file->completion = trim( $match[3] );
    }
    else
    {
        $file->hash = null;
        $file->maintainer = null;
        $file->completion = null;
    }
    $regex = "/<!--\s*CREDITS:\s*(.+)\s*-->/U";
    $match = array();
    preg_match ( $regex , $contents , $match );
    if ( count( $match ) == 2 )
        $file->credits = str_replace( ' ' , '' , trim( $match[1] ) );
    else
        $file->credits = '';
}

function captureGitValues( $lang , & $output )
{
    $cwd = getcwd();
    chdir( $lang );
    $fp = popen( "git --no-pager log --name-only" , "r" );
    $hash = null;
    $date = null;
    $utct = new DateTimeZone( "UTC" );
    while ( ( $line = fgets( $fp ) ) !== false )
    {
        if ( substr( $line , 0 , 7 ) == "commit " )
        {
            $hash = trim( substr( $line , 7 ) );
            continue;
        }
        if ( strpos( $line , 'Date:' ) === 0 )
        {
            $date = trim( substr( $line , 5 ) );
            $date = DateTime::createFromFormat ( "D M d H:i:s Y T" , $date );
            $date->setTime( 0 , 0 );
            continue;
        }
        if ( trim( $line ) == "" )
            continue;
        if ( substr( $line , 0 , 4 ) == '    ' )
            continue;
        if ( strpos( $line , ': ' ) > 0 )
            continue;
        $filename = trim( $line );
        if ( isset( $output[$filename][$lang] ) )
            continue;
        $output[$filename][$lang]['hash'] = $hash;
        $output[$filename][$lang]['date'] = $date;
    }
    pclose( $fp );
    chdir( $cwd );
}

function computeSyncStatus( $enFiles , $trFiles , $gitData , $lang )
{
    $now = new DateTime( 'now' );
    foreach( $enFiles as $filename => $enFile )
    {
        if ( isset( $gitData[ $filename ]['en'] ) )
        {
            $enFile->hash = $gitData[ $filename ]['en']['hash'];
            $enFile->date = $gitData[ $filename ]['en']['date'];
        }
        else
            print "Warn: No hash for en/$filename\n";
        $trFile = isset( $trFiles[ $filename ] ) ? $trFiles[ $filename ] : null;
        // Untranslated (default)
        if ( $trFile == null )
        {
            $enFile->syncStatus = FileStatusEnum::Untranslated;
            continue;
        }
        else
        {
            $trFile->syncStatus = FileStatusEnum::ExistsInEnTree;
            if ( isset( $gitData[ $filename ][ $lang ] ) )
                $trFile->date = $gitData[ $filename ][ $lang ]['date'];
        }
        // RevTagProblem
        if ( $trFile->hash == null || ( strlen( $enFile->hash ) != strlen( $trFile->hash ) ) )
        {
            $enFile->syncStatus = FileStatusEnum::RevTagProblem;
            continue;
        }
        // TranslatedWip
        if ( $trFile->completion != null && $trFile->completion != "ready" )
        {
            $enFile->syncStatus = FileStatusEnum::TranslatedWip;
            continue;
        }
        // TranslatedOk
        // TranslatedOld
        // TranslatedCritial
        if ( $enFile->hash == $trFile->hash )
            $enFile->syncStatus = FileStatusEnum::TranslatedOk;
        else
        {
            $enFile->syncStatus = FileStatusEnum::TranslatedOld;
            if ( $enFile->date == null
              || $trFile->date == null
              || $now->diff( $enFile->date , true )->days > 30
              || $now->diff( $trFile->date , true )->days > 30 )
            {
                $enFile->syncStatus = FileStatusEnum::TranslatedCritial;
            }
        }
    }
}

// Output

function print_html_all( $enFiles , $trFiles , $lang )
{
    print_html_header( $lang );
    //print_html_introduction();
    //print_html_translations();
    //print_html_filesumary();
    print_html_files( $enFiles , $trFiles , $lang );
    //print_html_wip();
    //print_html_revtagproblem();
    //print_html_untranslated();
    print_html_notinen( $enFiles );
    print_html_footer();
}

function print_html_header( $lang )
{
    $date = date("r");
    print <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<style type="text/css">
body { margin:0px 0px 0px 0px; background-color:#F0F0F0; font-family: sans-serif; text-align: center; }
a { color: black; }
h1 { color: #FFFFFF; }
table { margin-left: auto; margin-right: auto; text-align: left; border-spacing: 1px; }
th { color: white; background-color: #666699; padding: 0.2em; text-align: center; vertical-align: middle; }
td { padding: 0.2em 0.3em; }
.o { white-space: nowrap; overflow: hidden; max-width: 3em; }
.c { text-align: center; }
.r { text-align: right; }
.b { font-weight: bold; }
.white { color: white; }
.black { color: black; }
.bgblue { background-color: #666699;}
.bggray { background-color: #dcdcdc;}
.bggreen { background-color: #68d888;}
.bgorange { background-color: #f4a460;}
.bgred { background-color: #ff6347;}
.bgyellow { background-color: #eee8aa;}
</style>
</head>
<body>

<div id="header" style="background-color: #9999CC;">
<h1 style="margin: 0; padding: 0.5em;">Status of the translated PHP Manual</h1>
<p style="font-size: small; margin: 0; padding: 1em;">Generated: $date / Language: $lang</p>
</div>

HTML;
}

function print_html_menu( $href )
{
    print <<<HTML

<a id="$href"/>
<p><a href="#intro">Introduction</a> | <a href="#translators">Translators</a> | <a href="#filesummary">File summary</a> | <a href="#files">Files</a> | <a href="#wip">Work in progress</a> | <a href="#revtag">Revision tag problem</a> | <a href="#untranslated">Untranslated files</a> | <a href="#notinen">Not in EN tree</a></p>

HTML;
}

function print_html_notinen($enFiles)
{
    $exists = false;
    $count = 0;
    foreach( $enFiles as $key => $en )
    {
        if ( $en->syncStatus == FileStatusEnum::Untranslated ) {
            $exists = true;
            $count++;
        }
    }

    if (!$exists) return;

    print <<<HTML

<p>&nbsp;</p>
<a name="notinen"></a>
<table width="600" border="0" cellpadding="3" cellspacing="1" align="center">
 <tr>
  <th>Not in EN Tree ($count files):</th>
  <th>kb</th>
 </tr>
HTML;

    $path = null;
    foreach( $enFiles as $key => $en )
    {
        if ( $en->syncStatus != FileStatusEnum::Untranslated )
            continue;
        if ( !preg_match( "/^.*\.xml\$/", $en->name ) || $en->name === "versions.xml")
            continue;
        if ( $path !== $en->path )
        {
            $path = $en->path;
            $path2 = $path == '' ? '/' : $path;
            print " <tr><th class='blue' colspan='2'>$path2</th></tr>";
        }
        $size = $en->size < 1024 ? 1 : floor( $en->size / 1024 );

    print <<<HTML

 <tr class=bggray>
  <td class="c">$en->name</td>
  <td class="c">$size</td>
 </tr>
HTML;
    }
    print "</table>\n";
    print "<p>&nbsp;</p>\n";
}

function print_html_footer()
{
    print <<<HTML
</body>
</html>
HTML;
}

function print_html_files( $enFiles , $trFiles , $lang )
{
    print_html_menu( 'files' );
    print <<<HTML
<table>
 <tr>
  <th rowspan="2">Translated file</th>
  <th colspan="2">Hash</th>
  <th colspan="3">Size in kB</th>
  <th colspan="3">Age in days</th>
  <th rowspan="2">Maintainer</th>
  <th rowspan="2">Status</th>
 </tr>
 <tr>
  <th>en</th>
  <th>$lang</th>
  <th>en</th>
  <th>$lang</th>
  <th>diff</th>
  <th>en</th>
  <th>$lang</th>
  <th>diff</th>
 </tr>

HTML;

    $now = new DateTime( 'now' );
    $path = null;
    foreach( $enFiles as $key => $en )
    {
        if ( $en->syncStatus == FileStatusEnum::TranslatedOk )
            continue;
        if ( $en->syncStatus == FileStatusEnum::Untranslated )
            continue;
        $tr = $trFiles[ $key ];
        if ( $path !== $en->path )
        {
            $path = $en->path;
            $path2 = $path == '' ? '/' : $path;
            print " <tr><th colspan='11' class='blue c'>$path2</th></tr>";
        }
        switch( $en->syncStatus )
        {
            case FileStatusEnum::RevTagProblem:     $bg = 'bgorange'; break;
            case FileStatusEnum::TranslatedOk:      $bg = 'bggreen' ; break;
            case FileStatusEnum::TranslatedOld:     $bg = 'bgyellow'; break;
            case FileStatusEnum::TranslatedCritial: $bg = 'bgred'   ; break;
            default:                                $bg = 'bggray'  ; break;
        }
        $df = "https://git.php.net/?p=doc/en.git;a=blobdiff;f=$key;hb={$en->hash};hpb={$tr->hash};";
        $nm = "<a href='$df'>{$en->name}</a>";
        if ( $en->syncStatus == FileStatusEnum::RevTagProblem )
            $nm = $en->name;
        $h1 = "<a href='http://git.php.net/?p=doc/en.git;a=blob;f=$key;hb={$en->hash}'>{$en->hash}</a>";
        $h2 = "<a href='http://git.php.net/?p=doc/en.git;a=blob;f=$key;hb={$tr->hash}'>{$tr->hash}</a>";
        $s1 = $en->size < 1024 ? 1 : floor( $en->size / 1024 );
        $s2 = $tr->size < 1024 ? 1 : floor( $tr->size / 1024 );
        $s3 = $s2 - $s1;
        $a1 = $now->diff( $en->date )->days;
        $a2 = $now->diff( $tr->date )->days;
        $a3 = $a2 - $a1;
        $ma = $tr->maintainer;
        $st = $tr->completion;
        print <<<HTML
 <tr class="$bg">
  <td class="l">$nm</td>
  <td class="o">$h1</td>
  <td class="o">$h2</td>
  <td class="r">$s1</td>
  <td class="r">$s2</td>
  <td class="r">$s3</td>
  <td class="r">$a1</td>
  <td class="r">$a2</td>
  <td class="r">$a3</td>
  <td class="c">$ma</td>
  <td class="c">$st</td>
 </tr>
HTML;
    }
    print "</table>\n";
}
