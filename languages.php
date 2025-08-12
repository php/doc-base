<?php /*
+------------------------------------------------------------------------------+
| Copyright (c) 1997-2023 The PHP Group                                        |
+------------------------------------------------------------------------------+
| This source file is subject to version 3.01 of the PHP license,              |
| that is bundled with this package in the file LICENSE, and is                |
| available through the world-wide-web at the following url:                   |
| https://www.php.net/license/3_01.txt.                                        |
| If you did not receive a copy of the PHP license and are unable to           |
| obtain it through the world-wide-web, please send a note to                  |
| license@php.net, so we can mail you a copy immediately.                      |
+------------------------------------------------------------------------------+
| Authors:     André L F S Bacci <ae php.net>                                  |
+------------------------------------------------------------------------------+
| Description: Creates and maintains manual languages checkouts.               |
+------------------------------------------------------------------------------+
*/

//     dir     manual  revcheck  cloneUrl                          label

lang( "de"    , true  , true  , "git@github.com:php/doc-de.git" , "German" );
lang( "en"    , true  , false , "git@github.com:php/doc-en.git" , "English" );
lang( "es"    , true  , true  , "git@github.com:php/doc-es.git" , "Spanish" );
lang( "fr"    , true  , true  , "git@github.com:php/doc-fr.git" , "French" );
lang( "it"    , true  , true  , "git@github.com:php/doc-it.git" , "Italian" );
lang( "ja"    , true  , true  , "git@github.com:php/doc-ja.git" , "Japanese" );
lang( "pl"    , false , true  , "git@github.com:php/doc-pl.git" , "Polish" );
lang( "pt_BR" , true  , true  , "git@github.com:php/doc-pt_br.git" , "Brazilian Portuguese" );
lang( "ru"    , true  , true  , "git@github.com:php/doc-ru.git" , "Russian" );
lang( "tr"    , true  , true  , "git@github.com:php/doc-tr.git" , "Turkish" );
lang( "uk"    , true  , true  , "git@github.com:php/doc-uk.git" , "Ukrainian" );
lang( "zh"    , true  , true  , "git@github.com:php/doc-zh.git" , "Chinese (Simplified)" );

if ( count( $argv ) == 1 )
    print_usage();
else
    run();
return;

function print_usage()
{
    print <<<USAGE
usage: [--clone] [--undo] [--pull] [--mark] [--quiet]
       [--list-cvs] [--list-ssv] [--rev] [--all]
       [lang] [lang] ...

Options that operates on local repositories:

   --clone      Clone a sibling language repo, if not exists
   --undo       Restore and clean up repositories to a pristine state
   --pull       Executes git pull
   --mark       Creates/deletes marking files
   --quiet      Set this option on git commands

Options that output simple listings:

   --list-csv   List selected langcodes separated with commas
   --list-ssv   List selected langcodes separated with spaces

Options that select more languages to operate:

   --rev        Include languages with revcheck flag
   --all        Include all languages
   --base       Include doc-base repo


USAGE;
}

function lang( string $code , bool $manual , bool $revcheck , string $cloneUrl , string $label )
{
    $lang = new Lang( $code , $manual , $revcheck , $cloneUrl , $label );
    Conf::$knowLangs[ $code ] = $lang;
}

class Conf
{
    static array $langs     = []; // languages to operate
    static array $knowLangs = []; // all declared languages

    static bool $clone  = false;
    static bool $undo   = false;
    static bool $pull   = false;
    static bool $mark   = false;

    static string|null $quiet = null;

    static bool $listCsv = false;
    static bool $listSsv = false;
}

class Lang
{
    function __construct
        ( public string $code
        , public bool   $manual
        , public bool   $revcheck
        , public string $cloneUrl
        , public string $label
        , public string $path = "" )
    {
        $this->path = realpath( __DIR__ . '/..' ) . "/{$code}";
        $this->path = str_replace( "\\" , '/' , $this->path );
    }
}

function run()
{
    global $argv;
    array_shift( $argv );
    foreach( $argv as $arg )
    {
        switch( $arg )
        {
            case "--clone": Conf::$clone = true; break;
            case "--undo":  Conf::$undo  = true; break;
            case "--pull":  Conf::$pull  = true; break;
            case "--mark":  Conf::$mark  = true; break;

            case "--quiet": Conf::$quiet = "--quiet"; break;

            case "--list-csv": Conf::$listCsv = true; break;
            case "--list-ssv": Conf::$listSsv = true; break;

            case "--all":   langAddAll(); break;
            case "--rev":   langAddRev(); break;
            case "--base":  langDocbase(); break;

            default:        langAdd( $arg ); break;
        }
    }

    // Default: languages with build manual flag

    if ( count( Conf::$langs ) == 0 )
        foreach( Conf::$knowLangs as $lang )
            if ( $lang->manual )
                Conf::$langs[ $lang->code ] = $lang;

    // Exclusive listing commands

    if ( Conf::$listCsv || Conf::$listSsv )
    {
        $lst = [];
        foreach( Conf::$langs as $lang )
            $lst[] = $lang->code;
        if ( Conf::$listCsv )
            print implode( ',' , $lst );
        else
            print implode( ' ' , $lst );
        exit;
    }

    // Composite commands

    echo "Selected languages:";
    foreach( Conf::$langs as $lang )
        echo ' ' . $lang->code;
    echo "\n";

    gitAll();
    dirMark();
}

function langAdd( string $langCode )
{
    foreach( Conf::$knowLangs as $lang )
    {
        if ( $lang->code == $langCode )
        {
            Conf::$langs[ $lang->code ] = $lang;
            return;
        }
    }
    fprintf( STDERR , "Unknown option or langcode: $langCode\n" );
    exit(-1);
}

function langAddAll()
{
    foreach( Conf::$knowLangs as $lang )
        Conf::$langs[ $lang->code ] = $lang;
}

function langAddRev()
{
    foreach( Conf::$knowLangs as $lang )
        if ( $lang->manual || $lang->revcheck )
            Conf::$langs[ $lang->code ] = $lang;
}

function langDocbase()
{
    $code = basename( __DIR__ );
    $lang = new Lang( $code , false , false , "" , "" );
    Conf::$langs[ $lang->code ] = $lang;
}

function gitAll()
{
    foreach( Conf::$langs as $lang )
    {
        gitClone( $lang );
        gitUndo ( $lang );
        gitPull ( $lang );
    }
}

function gitClone( Lang $lang )
{
    if ( Conf::$clone == false )
        return;
    if ( $lang->cloneUrl == "" )
        return;

    if ( file_exists( $lang->path ) )
    {
        echo "clone {$lang->code} (already exists)\n";
        return;
    }
    else
        echo "clone {$lang->code}\n";

    $cmd = array( 'git' , 'clone' , Conf::$quiet , $lang->cloneUrl , $lang->path );
    cmdExecute( $cmd );
}

function gitUndo( Lang $lang )
{
    if ( Conf::$undo == false )
        return;

    if ( ! file_exists( $lang->path ) )
    {
        echo "undo  {$lang->code}: path does not exists, skipping.\n";
        return;
    }
    else
        echo "undo  {$lang->code}\n";

    $cmd = array( 'git' , '-C' , $lang->path , 'restore' , Conf::$quiet , '.' );
    cmdExecute( $cmd );

    $cmd = array( 'git' , '-C' , $lang->path , 'clean' , Conf::$quiet , '-f' , '-d' );
    cmdExecute( $cmd );

    $cmd = array( 'git' , '-C' , $lang->path , 'clean' , '--quiet' , '-fdx' );
    cmdExecute( $cmd );
}

function gitPull( Lang $lang )
{
    if ( Conf::$pull == false )
        return;

    if ( ! file_exists( $lang->path ) )
    {
        echo "pull  {$lang->code}: path does not exists, skipping.\n";
        return;
    }
    else
        echo "pull  {$lang->code}\n";

    $cmd = array( 'git' , '-C' , $lang->path , 'pull' , Conf::$quiet );
    cmdExecute( $cmd );
}

function cmdExecute( array $parts )
{
    $escaped = [];
    foreach( $parts as $part )
        if ( $part != null )
            $escaped[] = escapeshellarg( $part );

    $cmd = implode( ' ' , $escaped );
    $rsc = null;
    $ret = passthru( $cmd , $rsc );

    if ( $ret === false || $rsc != 0 )
    {
        echo "\nCommand failed, aborting: $cmd\n\n";
        exit(-1);
    }
}

function dirMark()
{
    if ( Conf::$mark == false )
        return;

    foreach( Conf::$langs as $lang )
    {
        $text = $lang->label;

        if ( $lang->manual ) // Flags langDir as manual build
        {
            $path = "{$lang->path}/BUILDMAN";

            if ( $lang->manual && ! file_exists( $path ) )
                file_put_contents( $path , $text );
            if ( ! $lang->manual && file_exists( $path ) )
                unlink( $path );
        }

        if ( $lang->manual ) // Flags langDir as genrevdb.php
        {
            $path = "{$lang->path}/BUILDREV";

            if ( $lang->revcheck && ! file_exists( $path ) )
                file_put_contents( $path , $text );
            if ( ! $lang->revcheck && file_exists( $path ) )
                unlink( $path );
        }
    }
}
