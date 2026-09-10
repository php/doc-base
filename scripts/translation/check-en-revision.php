<?php /*
+----------------------------------------------------------------------+
| Copyright (c) 1997-2026 The PHP Group                                |
+----------------------------------------------------------------------+
| This source file is subject to version 3.01 of the PHP license,      |
| that is bundled with this package in the file LICENSE, and is        |
| available through the world-wide-web at the following url:           |
| https://www.php.net/license/3_01.txt.                                |
| If you did not receive a copy of the PHP license and are unable to   |
| obtain it through the world-wide-web, please send a note to          |
| license@php.net, so we can mail you a copy immediately.              |
+----------------------------------------------------------------------+

# Description

Check that the EN-Revision tag of each translated file matches the latest
commit hash of the corresponding doc-en file.                          */

require_once __DIR__ . '/libqa/all.php';
require_once __DIR__ . '/lib/RevtagParser.php';

$argv   = new ArgvParser( $argv );
$argv->consume( position: 0 ); // script name
$help   = $argv->consume( equals: "--help" ) ?? $argv->consume( equals: "-h" );
$lang   = $argv->consume( prefix: "--lang=" );
$github = $argv->consume( equals: "--github" );
$files  = [];
foreach ( $argv->residual() as $arg )
    if ( strlen( $arg ) > 0 && $arg[0] != '-' )
    {
        $files[] = $arg;
        $argv->use( $arg );
    }
$argv->complete();

if ( $help !== null )
{
    fwrite( STDERR , "Usage: check-en-revision.php [--lang=xx] [--github] [files...]\n\n" );
    fwrite( STDERR , "Reads file names from the command line, or from standard input when none\n" );
    fwrite( STDERR , "are given. Paths are relative to the translation directory.\n\n" );
    fwrite( STDERR , "See https://github.com/php/doc-base/tree/master/scripts/translation#readme for more info.\n" );
    exit( 0 );
}

$lang  = requireLang( $lang );
$files = $files === [] ? readPathsFromStdin() : $files;

// -- Setup -----------------------------------------------------------------

/**
 * Language directory, given by --lang= or by the last configure.php run.
 */
function requireLang( ?string $lang ) : string
{
    if ( $lang !== null && $lang !== '' )
        return $lang;

    $file = __DIR__ . '/../../temp/lang';

    if ( ! file_exists( $file ) )
    {
        fwrite( STDERR , "No language to process. Run 'doc-base/configure.php' or use '--lang='.\n" );
        exit( 1 );
    }

    return trim( file_get_contents( $file ) );
}

/**
 * File names read from standard input, one per line, .xml only. This is how a
 * CI job hands over the list of files a pull request touches.
 */
function readPathsFromStdin() : array
{
    $paths = [];

    foreach ( explode( "\n" , stream_get_contents( STDIN ) ) as $line )
    {
        $path = trim( $line );
        if ( $path !== '' && str_ends_with( $path , '.xml' ) )
            $paths[] = $path;
    }

    return $paths;
}

// -- doc-en access ---------------------------------------------------------

/**
 * Latest commit hash for a file in doc-en, or null when the file does not
 * exist (newly added file with no doc-en counterpart yet).
 */
function latestEnCommit( string $file ) : ?string
{
    $command = sprintf(
        'git -C en log -1 --format=%%H -- %s 2>/dev/null' ,
        escapeshellarg( $file )
    );

    $hash = trim( (string) shell_exec( $command ) );

    return $hash !== '' ? $hash : null;
}

// -- Main ------------------------------------------------------------------
//
// Expected layout: 'en' and the translation directory side by side, as after a
// doc-base/configure.php run.

$violations = [];
$checked    = 0;

foreach ( $files as $file )
{
    $target = "$lang/$file";

    if ( ! is_file( $target ) )
        continue;

    if ( ! is_file( "en/$file" ) )
        continue;

    $targetXml = file_get_contents( $target );
    $revtag    = RevtagParser::parseXmlText( $targetXml );

    if ( $revtag->doNotTranslate )
        continue;

    $checked++;

    $declared = $revtag->revision;
    $latest   = latestEnCommit( $file );

    if ( $latest === null )
        continue;

    if ( $declared === $latest )
        continue;

    $violations[] = [ $file , $declared , $latest ];
}

foreach ( $violations as [ $file , $declared , $latest ] )
{
    $absent  = $declared === '' ? ' (absent)' : '';
    $message = sprintf(
        'EN-Revision %s%s does not match latest doc-en commit %s' ,
        $declared , $absent , $latest
    );

    if ( $github !== null )
        printf( "::error file=%s::%s\n" , $file , $message );
    else
        printf( "%s/%s: %s\n" , $lang , $file , $message );
}

fprintf( STDERR , "checked=%d outdated=%d\n" , $checked , count( $violations ) );

exit( $violations === [] ? 0 : 1 );
