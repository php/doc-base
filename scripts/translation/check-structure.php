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

Compare the block structure of translated files with doc-en, at the revision
each file declares to mirror.                                          */

require_once __DIR__ . '/libqa/all.php';
require_once __DIR__ . '/lib/XmlUtil.php';
require_once __DIR__ . '/lib/RevtagParser.php';

// Attributes that take part in a signature. href and xpointer come from
// XInclude: a translated target selects nothing and breaks the build, so they
// must mirror doc-en exactly.

const STRUCTURAL_ATTRIBUTES = [ 'role' , 'choice' , 'class' , 'xml:id' , 'rep' , 'href' , 'xpointer' ];

// Elements whose content is text. The element itself is recorded, its content
// is not walked: the prose inside is the translator's business.

const TEXT_CONTAINERS = [
    'para' , 'simpara' , 'term' , 'title' , 'titleabbrev' , 'refpurpose' ,
    'refname' , 'member' , 'entry' , 'literallayout' , 'programlisting' ,
    'screen' , 'seg' , 'segtitle' , 'synopsis' ,
];

$argv     = new ArgvParser( $argv );
$argv->consume( position: 0 ); // script name
$help     = $argv->consume( equals: "--help" ) ?? $argv->consume( equals: "-h" );
$lang     = $argv->consume( prefix: "--lang=" );
$github   = $argv->consume( equals: "--github" );
$files    = [];
foreach ( $argv->residual() as $arg )
    if ( strlen( $arg ) > 0 && $arg[0] != '-' )
    {
        $files[] = $arg;
        $argv->use( $arg );
    }
$argv->complete();

if ( $help !== null )
{
    fwrite( STDERR , "Usage: check-structure.php [--lang=xx] [--github] [files...]\n\n" );
    fwrite( STDERR , "Reads file names from the command line, or from standard input when none\n" );
    fwrite( STDERR , "are given. Paths are relative to the translation directory.\n\n" );
    fwrite( STDERR , "See https://github.com/php/doc-base/tree/master/scripts/translation#readme for more info.\n" );
    exit( 0 );
}

$lang     = requireLang( $lang );
$files    = $files === [] ? readPathsFromStdin() : $files;

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

// -- Skeleton construction -------------------------------------------------

/**
 * Signature of an element: its name, followed by its structural attributes.
 * <refsect1 role="description"> gives "refsect1(role=description)".
 */
function elementSignature( DOMElement $element ) : string
{
    $attributes = [];

    foreach ( STRUCTURAL_ATTRIBUTES as $name )
    {
        $value = $element->getAttribute( $name );
        if ( $value !== '' )
            $attributes[] = "$name=$value";
    }

    if ( $attributes === [] )
        return $element->nodeName;

    return $element->nodeName . '(' . implode( ',' , $attributes ) . ')';
}

/**
 * Walks the tree depth first, appending the signature of each element, indented
 * by its depth. Text containers are recorded but not entered.
 */
function collectSkeleton( DOMElement $element , string $depth , array & $skeleton ) : void
{
    // Translator credits exist only on the translation side, and would show up
    // as a difference on every file that carries them.

    $isTranslatorCredits = $element->nodeName === 'authorgroup'
        && str_starts_with( $element->getAttribute( 'xml:id' ) , 'translators' );

    if ( $isTranslatorCredits )
        return;

    $skeleton[] = $depth . elementSignature( $element );

    if ( in_array( $element->nodeName , TEXT_CONTAINERS , true ) )
        return;

    foreach ( $element->childNodes as $child )
        if ( $child->nodeType === XML_ELEMENT_NODE )
            collectSkeleton( $child , $depth . ' ' , $skeleton );
}

/**
 * Skeleton of an XML fragment: the flat list of its block element signatures.
 * Undeclared entities are left to XmlUtil::loadText(), which recovers from
 * them; both sides go through the same loader, so the treatment is symmetric.
 */
function buildSkeleton( string $xml ) : array
{
    $document = XmlUtil::loadText( $xml );

    if ( $document->documentElement === null )
        return [ '<<INVALID>>' ];

    $skeleton = [];
    collectSkeleton( $document->documentElement , '' , $skeleton );

    return $skeleton;
}

// -- doc-en access and comparison ------------------------------------------

/**
 * Contents of a doc-en file at a given revision, or null when the file did not
 * exist at that revision.
 */
function docEnFileAtRevision( string $hash , string $file ) : ?string
{
    $command = sprintf(
        'git -C %s show %s:%s 2>/dev/null' ,
        escapeshellarg( 'en' ) ,
        escapeshellarg( $hash ) ,
        escapeshellarg( $file )
    );

    $contents = shell_exec( $command );

    return ( $contents === null || $contents === '' ) ? null : $contents;
}

/**
 * First position where two skeletons differ, as [ enLine , targetLine ], or
 * null when they are identical. A missing line is reported as "(none)".
 */
function firstDivergence( array $enSkeleton , array $targetSkeleton ) : ?array
{
    $length = max( count( $enSkeleton ) , count( $targetSkeleton ) );

    for ( $i = 0 ; $i < $length ; $i++ )
    {
        $enLine     = $enSkeleton[ $i ] ?? '';
        $targetLine = $targetSkeleton[ $i ] ?? '';

        if ( $enLine !== $targetLine )
            return [
                trim( $enSkeleton[ $i ] ?? '(none)' ) ,
                trim( $targetSkeleton[ $i ] ?? '(none)' ) ,
            ];
    }

    return null;
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

    $targetXml = file_get_contents( $target );
    $revtag    = RevtagParser::parseXmlText( $targetXml );

    if ( $revtag->doNotTranslate )
        continue;

    // No revision tag: nothing tells us which doc-en version to compare with.
    // qaxml-revtag.php is the script that reports those files.

    if ( $revtag->revision === '' )
        continue;

    $enXml = docEnFileAtRevision( $revtag->revision , $file );

    // File absent on the doc-en side at that revision: newly added, renamed.

    if ( $enXml === null )
        continue;

    $checked++;

    $enSkeleton     = buildSkeleton( $enXml );
    $targetSkeleton = buildSkeleton( $targetXml );
    $divergence     = firstDivergence( $enSkeleton , $targetSkeleton );

    if ( $divergence === null )
        continue;

    [ $enLine , $targetLine ] = $divergence;

    $violations[] = [
        $file , $enLine , $targetLine ,
        count( $enSkeleton ) , count( $targetSkeleton ) ,
    ];
}

foreach ( $violations as [ $file , $enLine , $targetLine , $enCount , $targetCount ] )
{
    $message = sprintf(
        'structure differs from doc-en (EN: %s | translation: %s) [blocks EN=%d translation=%d]' ,
        $enLine , $targetLine , $enCount , $targetCount
    );

    // Under GitHub Actions the path must be relative to the repository being
    // annotated, so that the alert lands on the right file of the pull request.

    if ( $github !== null )
        printf( "::error file=%s::%s\n" , $file , $message );
    else
        printf( "%s/%s: %s\n" , $lang , $file , $message );
}

fprintf( STDERR , "checked=%d divergent=%d\n" , $checked , count( $violations ) );

exit( $violations === [] ? 0 : 1 );
