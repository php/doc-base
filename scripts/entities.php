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

XML Entity processing has more in common with DOMDocumentFragment than
DOMElement. In other words, simple text and multi rooted XML files
are valid <!ENTITY> contents, whereas they are not valid XML documents.

Also, namespaces do not automatically "cross" between a parent
document and their includes, even if they are included in the same
file, as local textual entities. <!ENTITY>s are, for all intended
purposes, separated documents, with separated namespaces and have
*expected* different default namespaces.

So each one of, possibly multiple, "root" XML elements inside an
fragment need to be annotated with default namespace, even if the
"root" element occurs surrounded by text. For example:

- "text<tag>text</tag>", need one namespace, or it is invalid, and;
- "<tag></tag><tag></tag>", need TWO namespaces, or it is also invalid.

# Output

This script collects bundled and individual entity files (detailed
below), at some expected relative paths, and generates an
.entities.ent file, in a sibling position to manual.xml.in.

The output .entities.ent file has no duplications, so collection
order is important to keep the necessary operational semantics. Here,
newer loaded entities takes priority (overwrites) over previous one.
Note that this is the reverse of <!ENTITY> convention, where
duplicated entity names are ignored. The priority order used here
is important to allow detecting cases where "constant" entities
are being overwriten, or if translatable entities are missing
translations.

# Individual tracked entities, or `.xml` files at `entities/`

As explained above, the individual entity contents are not really
valid XML *documents*, they are only at most valid XML *fragments*.

Yet, individual entities are stored in entities/ as .xml files, for
two reasons: first, text editors in general can highlights XML syntax,
and second, this allows normal revision tracking on then, without
requiring weird changes on `revcheck.php`.

# Bundled entities files, group tracked

For very small textual entities, down to simple text words or single
tag elements, that may never change, individual entity tracking is
an overkill. This script also loads bundled entities files, at
some expected locations, with specific semantics.

These bundle files are really normal XML files, correctly annotated
with XML namespaces used on manual, so any individual exported entity
have corret XML namespace annotations. These bundle entity files
are revcheck tracked normaly, but are not included in manual.xml.in,
as they only participate in general entity loading, described above.

- global.ent        - expected untranslated
- manual.ent        - expected translated
- lang/entities/*   - expected translated

*/

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );

const PARTIAL_IMPL = true; // For while spliting and bundle convertion are incomplete

if ( count( $argv ) < 2 || in_array( '--help' , $argv ) || in_array( '-h' , $argv ) )
{
    fwrite( STDERR , "\nUsage: {$argv[0]} [--debug] entitiesDir [entitiesDir]\n\n" );
    return;
}

$filename = Entities::rotateOutputFile();

$langs = [];
$normal = true; // configure.php mode
$debug = false; // detailed output

for( $idx = 1 ; $idx < count( $argv ) ; $idx++ )
    if ( $argv[$idx] == "--debug" )
    {
        $normal = false;
        $debug = true;
    }
    else
        $langs[] = $argv[$idx];

if ( $normal )
    print "Creating .entities.ent...";
else
    print "Creating .entities.ent in debug mode.\n";

loadEnt( __DIR__ . "/../global.ent"  , global: true , warnMissing: true );
foreach( $langs as $lang )
{
    loadEnt( __DIR__ . "/../../$lang/global.ent" , global: true );
    loadEnt( __DIR__ . "/../../$lang/manual.ent" , translate: true , warnMissing: true );
    loadEnt( __DIR__ . "/../../$lang/remove.ent" , remove: true );
    loadDir( $langs , $lang );
}

Entities::writeOutputFile();
Entities::checkReplaces( $debug );

echo " done: " , Entities::$countTotalGenerated , " entities";
if ( Entities::$countUnstranslated  > 0 )
    echo ", " , Entities::$countUnstranslated , " untranslated";
if ( Entities::$countConstantReplaced > 0 )
    echo ", " , Entities::$countConstantReplaced , " global replaced";
if ( Entities::$countRemoveReplaced  > 0 )
    echo ", " , Entities::$countRemoveReplaced , " to be removed";
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
    public static int $countConstantReplaced = 0;
    public static int $countUnstranslated = 0;
    public static int $countRemoveReplaced = 0;
    public static int $countTotalGenerated = 0;

    private static string $filename = __DIR__ . "/../.entities.ent"; // sibling of .manual.xml

    private static array $entities = [];    // All entities, overwriten
    private static array $global = [];      // Entities from global.ent files
    private static array $replace = [];     // Entities expected replaced / translated
    private static array $remove = [];      // Entities expected removed
    private static array $count = [];       // Name / Count
    private static array $slow = [];        // External entities, slowless, overwrite

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

        if ( ! isset( Entities::$count[$name] ) )
            Entities::$count[$name] = 1;
        else
            Entities::$count[$name]++;
    }

    static function slow( string $path )
    {
        if ( isset( $slow[$path] ) )
            fwrite( STDERR , "Unexpected physical file ovewrite: $path\n" );
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
        Entities::$countConstantReplaced = 0;
        Entities::$countUnstranslated = 0;
        Entities::$countRemoveReplaced = 0;

        foreach( Entities::$entities as $name => $text )
        {
            $replaced = Entities::$count[$name] - 1;
            $expectedConstant = in_array( $name , Entities::$global );
            $expectedReplaced = in_array( $name , Entities::$replace );
            $expectedRemoved  = in_array( $name , Entities::$remove );

            if ( $expectedConstant && $replaced != 0 )
            {
                Entities::$countConstantReplaced++;
                if ( $debug )
                    print "Expected global, replaced $replaced times:\t$name\n";
            }

            if ( $expectedReplaced && $replaced != 1 )
            {
                Entities::$countUnstranslated++;
                if ( $debug )
                    print "Expected translated, replaced $replaced times:\t$name\n";
            }

            if ( $expectedRemoved && $replaced != 0 )
            {
                Entities::$countRemoveReplaced++;
                if ( $debug )
                    print "Expected removed, replaced $replaced times:\t$name\n";
            }
        }
    }
}

function loadEnt( string $path , bool $global = false , bool $translate = false , bool $remove = false , bool $warnMissing = false )
{
    $absolute = realpath( $path );
    if ( $absolute === false )
        if ( PARTIAL_IMPL )
            return;
        else
            if ( $warnMissing )
                fwrite( STDERR , "\n  Missing entity file: $path\n" );
    $path = $absolute;

    $text = file_get_contents( $path );
    $text = str_replace( "&" , "&amp;" , $text );

    $dom = new DOMDocument( '1.0' , 'utf8' );
    if ( ! $dom->loadXML( $text ) )
        die( "XML load failed for $path\n" );

    $xpath = new DOMXPath( $dom );
    $list = $xpath->query( "/*/*" );

    foreach( $list as $ent )
    {
        // weird, namespace correting, DOMNodeList -> DOMDocumentFragment
        $other = new DOMDocument( '1.0' , 'utf8' );

        foreach( $ent->childNodes as $node )
            $other->appendChild( $other->importNode( $node , true ) );

        $name = $ent->getAttribute( "name" );
        $text = $other->saveXML();

        $text = str_replace( "&amp;" , "&" , $text );
        $text = rtrim( $text , "\n" );
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
            exit( "Not directory: $dir\n" );

    $files = scandir( $dir );
    $expectedReplaced = array_search( $lang , $langs ) > 0;

    foreach( $files as $file )
    {
        $path = realpath( "$dir/$file" );

        if ( is_dir( $path ) )
            continue;
        if ( str_starts_with( $file , '.' ) )
            continue;

        $text = file_get_contents( $path );
        $text = rtrim( $text , "\n" );

        loadXml( $path , $text , $expectedReplaced );
    }
}

function loadXml( string $path , string $text , bool $expectedReplaced )
{
    if ( trim( $text ) == "" )
    {
        fwrite( STDERR , "\n  Empty entity (should it be in remove.ent?): '$path' \n" );
        Entities::put( $pat , $text , remove: true );
        return;
    }

    $info = pathinfo( $path );
    $name = $info["filename"];

    $frag = "<frag>$text</frag>";

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
    $tmpDir = __DIR__ . "/entities";

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
