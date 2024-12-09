<?php /*
+----------------------------------------------------------------------+
| Copyright (c) 1997-2023 The PHP Group                                |
+----------------------------------------------------------------------+
| This source file is subject to version 3.01 of the PHP license,      |
| that is bundled with this package in the file LICENSE, and is        |
| available through the world-wide-web at the following url:           |
| https://www.php.net/license/3_01.txt.                                |
| If you did not receive a copy of the PHP license and are unable to   |
| obtain it through the world-wide-web, please send a note to          |
| license@php.net, so we can mail you a copy immediately.              |
+----------------------------------------------------------------------+
| Authors:     André L F S Bacci <ae php.net>                          |
+----------------------------------------------------------------------+
| Description: Collect individual entities into an .entities.ent file. |
+----------------------------------------------------------------------+

# Mental model, or things that I would liked to know 20 years prior

DTD Entity processing has more in common with DOMDocumentFragment than
DOMElement. In other words, simple text and multi rooted XML files
are valid <!ENTITY> contents, whereas they are not valid XML documents.

Also, namespaces do not automatically "cross" between a parent
document and their entities, even if they are included in the same
file, as local textual entities. <!ENTITY>s are, for all intended
purposes, separated documents, with separated namespaces and have
*expected* different default namespaces.

So each one of, possibly multiple, "root" XML elements inside an
fragment need to be annotated with default namespace, even if the
"root" element occurs surrounded by text. For example:

- "text<tag>text</tag>", need one namespace, or it is invalid, and;
- "<tag></tag><tag></tag>", need TWO namespaces, or it is also invalid.

# Output

This script collects grouped and individual XML Entity files
(detailed below), at some expected relative paths, and generates an
doc-base/temp/entities.ent file with their respective DTD Entities.

The output file has no duplications, so collection order is important
to keep the necessary operational semantics. Here, latter loaded entities
takes priority (overrides) an previous defined one. Note that this is the
reverse of DTD <!ENTITY> convention, where duplicated entity names are
ignored. The priority order used here is important to allow detecting
cases where global entities are being overwritten, or if expected
translatable entities are missing translations.

# Individual XML Entities, or `.xml` files at `entities/`

As explained above, the individual entity contents are not really
valid XML *documents*, they are only at most valid XML *fragments*.
More technically, these XML files are really well-balanced texts, per
https://www.w3.org/TR/xml-fragment/#defn-well-balanced .

Yet, individual entities are stored in entities/ as .xml files, for
two reasons: first, text editors in general can highlights XML syntax in
well-balanced texts; and second, this allows normal revision tracking
per file, without requiring weird changes on `revcheck.php`. Note that
is *invalid* to place XML declaration in these fragment files, at least
in files that are invalid XML documents (on multi-node rooted ones).

# Grouped entities files, file tracked

For very small textual entities, down to simple text words or single
tag elements that may never change, individual entity tracking is
an overkill. This script also loads grouped XML Entities files, at
some expected locations, with specific semantics.

These grouped files are really normal XML files, correctly annotated
with XML namespaces used on manuals, so any individual exported entity
has correct and clean XML namespace annotations. These grouped entity
files are tracked normally by revcheck, but are not directly included
in manual.xml.in, as they only participate in general entity loading,
described above.

- global.ent        - expected unreplaced
- manual.ent        - expected replaced (translated)
- remove.ent        - expected unused
- lang/entities/*   - expected replaced (translated)

*/

const PARTIAL_IMPL = true; // For while XML Entities are not fully implanted in all languages

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );

if ( count( $argv ) < 2 || in_array( '--help' , $argv ) || in_array( '-h' , $argv ) )
{
    fwrite( STDERR , "\nUsage: {$argv[0]} [--debug] langCode [langCode]\n\n" );
    return;
}

$filename = Entities::rotateOutputFile(); // idempotent

$langs = [];
$normal = true;
$debug = false;

for( $idx = 1 ; $idx < count( $argv ) ; $idx++ )
    if ( $argv[$idx] == "--debug" )
        $normal = false;
    else
        $langs[] = $argv[$idx];
$debug = ! $normal;

if ( $normal )
    print "Creating .entities.ent...";
else
    print "Creating .entities.ent in debug mode.\n";
$debug = ! $normal;

loadEnt( __DIR__ . "/../global.ent"  , global: true , warnMissing: true );
foreach( $langs as $lang )
{
    loadEnt( __DIR__ . "/../../$lang/global.ent" , global: true );
    loadEnt( __DIR__ . "/../../$lang/manual.ent" , translate: true , warnMissing: true );
    loadEnt( __DIR__ . "/../../$lang/remove.ent" , remove: true );
    loadDir( $langs , $lang );
    Entities::$debugUnique = false;
}

Entities::writeOutputFile();
Entities::checkReplaces( $debug );

echo " done: " , Entities::$countTotalGenerated , " entities";
if ( Entities::$countUnstranslated  > 0 )
    echo ", " , Entities::$countUnstranslated , " untranslated";
if ( Entities::$countReplacedGlobal > 0 )
    echo ", " , Entities::$countReplacedGlobal , " global replaced";
if ( Entities::$countReplacedRemove  > 0 )
    echo ", " , Entities::$countReplacedRemove , " remove replaced";
if ( Entities::$countDuplicated  > 0 )
    echo ", " , Entities::$countDuplicated , " duplicated (first language)";
echo ".\n";

exit;

class EntityData
{
    public function __construct(
        public string $path ,
        public string $name ,
        public string $text ) {}
}

class Entities
{
    private static string $filename = __DIR__ . "/../temp/entities.ent"; // idempotent

    private static array $entities = [];    // All entities, bi duplications
    private static array $global = [];      // Entities expected not replaced
    private static array $replace = [];     // Entities expected replaced / translated
    private static array $remove = [];      // Entities expected not replaced and not used
    private static array $unique = [];      // For detecting duplicated global+en entities
    private static array $count = [];       // Name / Count
    private static array $slow = [];        // External entities, slow, uncontrolled file overwrites

    public static bool $debugUnique = true; // Start on unique mode, disable on second language

    public static int $countUnstranslated = 0;
    public static int $countReplacedGlobal = 0;
    public static int $countReplacedRemove = 0;
    public static int $countTotalGenerated = 0;
    public static int $countDuplicated = 0;

    static function put( string $path , string $name , string $text , bool $global = false , bool $replace = false , bool $remove = false )
    {
        $entity = new EntityData( $path , $name , $text );
        Entities::$entities[ $name ] = $entity;

        if ( $global )
            Entities::$global[ $name ] = $name;

        if ( $replace )
            Entities::$replace[ $name ] = $name;

        if ( $remove )
            Entities::$remove[ $name ] = $name;

        if ( ! isset( Entities::$count[ $name ] ) )
            Entities::$count[$name] = 1;
        else
            Entities::$count[$name]++;

        if ( Entities::$debugUnique )
        {
            if ( isset( Entities::$unique[ $name ] ) )
            {
                Entities::$countDuplicated++;
                if ( Entities::$countDuplicated == 1 )
                    fwrite( STDERR , "\n" );
                fwrite( STDERR , "\n  Duplicated entity: $name\n" );
            }
            Entities::$unique[ $name ] = $entity;
        }
    }

    static function slow( string $path )
    {
        if ( isset( $slow[$path] ) )
            fwrite( STDERR , "Unexpected file overwrite: $path\n" );
        $slow[ $path ] = $path;
    }

    static function rotateOutputFile()
    {
        if ( file_exists( Entities::$filename ) )
            unlink( Entities::$filename );
        touch( Entities::$filename );
        Entities::$filename = realpath( Entities::$filename ); // only full paths on XML
    }

    static function writeOutputFile()
    {
        saveEntitiesFile( Entities::$filename , Entities::$entities );
    }

    static function checkReplaces( bool $debug )
    {
        Entities::$countTotalGenerated = count( Entities::$entities );
        Entities::$countUnstranslated = 0;
        Entities::$countReplacedGlobal = 0;
        Entities::$countReplacedRemove = 0;

        foreach( Entities::$entities as $name => $text )
        {
            $replaced = Entities::$count[$name] - 1;
            $expectedGlobal = in_array( $name , Entities::$global );
            $expectedReplaced = in_array( $name , Entities::$replace );
            $expectedRemoved  = in_array( $name , Entities::$remove );

            if ( $expectedGlobal && $replaced != 0 )
            {
                Entities::$countReplacedGlobal++;
                if ( $debug )
                    print "Expected global, replaced $replaced times:     $name\n";
            }

            if ( $expectedReplaced && $replaced != 1 )
            {
                Entities::$countUnstranslated++;
                if ( $debug )
                    print "Expected translated, replaced $replaced times: $name\n";
            }

            if ( $expectedRemoved && $replaced != 0 )
            {
                Entities::$countReplacedRemove++;
                if ( $debug )
                    print "Expected removed, replaced $replaced times:    $name\n";
            }
        }
    }
}

function loadEnt( string $path , bool $global = false , bool $translate = false , bool $remove = false , bool $warnMissing = false )
{
    $realpath = realpath( $path );
    if ( $realpath === false )
        if ( PARTIAL_IMPL )
            return;
        else
            if ( $warnMissing )
                fwrite( STDERR , "\n  Missing entity file: $path\n" );
    $path = $realpath;

    $text = file_get_contents( $path );
    $text = str_replace( "&" , "&amp;" , $text );

    $dom = new DOMDocument( '1.0' , 'utf8' );
    if ( ! $dom->loadXML( $text ) )
        die( "XML load failed for $path\n" );

    $xpath = new DOMXPath( $dom );
    $list = $xpath->query( "/*/*" );

    foreach( $list as $ent )
    {
        // weird, namespace correting, DOMNodeList -> DOMDocumentFragment transform
        $other = new DOMDocument( '1.0' , 'utf8' );

        foreach( $ent->childNodes as $node )
            $other->appendChild( $other->importNode( $node , true ) );

        $name = $ent->getAttribute( "name" );
        $text = $other->saveXML();

        $text = rtrim( $text , "\n" );
        $text = str_replace( "&amp;" , "&" , $text );
        $lines = explode( "\n" , $text );
        array_shift( $lines ); // remove XML declaration
        $text = implode( "\n" , $lines );

        Entities::put( $path , $name , $text , $global , $translate , $remove );
    }
}

function loadDir( array $langs , string $lang )
{
    global $debug;

    $dir = __DIR__ . "/../../$lang/entities";
    $dir = realpath( $dir );
    if ( $dir === false || ! is_dir( $dir ) )
        if ( PARTIAL_IMPL )
        {
            if ( $debug )
                print "Not a directory: $dir\n";
            return;
        }
        else
            exit( "Error: not a directory: $dir\n" );

    $files = scandir( $dir );
    $expectedReplaced = array_search( $lang , $langs ) > 0;

    foreach( $files as $file )
    {
        $path = realpath( "$dir/$file" );

        if ( str_starts_with( $file , '.' ) )
            continue;
        if ( is_dir( $path ) )
            continue;

        $text = file_get_contents( $path );
        $text = rtrim( $text , "\n" );

        loadXml( $path , $text , $expectedReplaced );
    }
}

function loadXml( string $path , string $text , bool $expectedReplaced )
{
    $info = pathinfo( $path );
    $name = $info["filename"];
    $frag = "<frag>$text</frag>";

    if ( trim( $text ) == "" )
    {
        if ( ! PARTIAL_IMPL )
            fwrite( STDERR , "\n  Empty entity (should it be in remove.ent?): '$path' \n" );
        Entities::put( $path , $name , $text );
        return;
    }

    $dom = new DOMDocument( '1.0' , 'utf8' );
    $dom->recover = true;
    $dom->resolveExternals = false;
    libxml_use_internal_errors( true );

    $res = $dom->loadXML( $frag );

    $err = libxml_get_errors();
    libxml_clear_errors();

    foreach( $err as $item )
    {
        $msg = trim( $item->message );
        if ( str_starts_with( $msg , "Entity '" ) && str_ends_with( $msg , "' not defined" ) )
            continue;

        fwrite( STDERR , "\n  XML load failed on entity file." );
        fwrite( STDERR , "\n    Path:  $path" );
        fwrite( STDERR , "\n    Error: $msg\n" );
        return;
    }

    Entities::put( $path , $name , $text , replace: $expectedReplaced );
}

function saveEntitiesFile( string $filename , array $entities )
{
    $tmpDir = __DIR__ . "/temp"; // idempotent

    $file = fopen( $filename , "w" );
    fputs( $file , "\n<!-- DO NOT COPY / DO NOT TRANSLATE - Autogenerated by entities.php -->\n\n" );

    foreach( $entities as $name => $entity )
    {
        $text = $entity->text;
        $quote = "";

        // If the text contains mixed quoting, keeping it
        // as an external file to avoid (re)quotation hell.

        if ( strpos( $text , "'" ) === false )
            $quote = "'";
        if ( strpos( $text , '"' ) === false )
            $quote = '"';

        if ( $quote == "" )
        {
            if ( $entity->path == "" )
            {
                $entity->path = $tmpDir . "/{$entity->path}.tmp";
                file_put_contents( $entity->path , $text );
            }
            fputs( $file , "<!ENTITY $name SYSTEM '{$entity->path}'>\n\n" );
            Entities::slow( $entity->path );
        }
        else
            fputs( $file , "<!ENTITY $name {$quote}{$text}{$quote}>\n\n" );
    }

    fclose( $file );
}
