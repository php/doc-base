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
| Description: Collect individual entities into an temp/entities.ent.  |
+----------------------------------------------------------------------+

# Mental model for DTD <!ENTITY>,
  or things that I would liked to know 20 years ago

DTD Entity contents have more in common with DOMDocumentFragment than
DOMElement. In other words, simple text and multi rooted XML fragments
are valid <!ENTITY> content, whereas they are not valid XML documents.

Also, namespaces do not automatically "cross" between a parent
document and their entities, even if they are included in the same
file, as local textual entities. Each <!ENTITY>s are, for all intended
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
to create some operational semantics. Here, latter loaded entities
takes priority (overrides) an previous defined one. Note that this is the
reverse of DTD <!ENTITY> convention, where duplicated entity names are
ignored. The priority order used here is important to allow detecting
cases where unique entities are being overwritten, or if expected
translatable entities are missing translations.

# Individual XML Entity files, or `.xml` files at `doc-lang/entities/`

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

# Grouped XML Entity files

For very small textual entities, down to simple text or single XML
elements that may never change, individual file tracking of entities
is an overkill. To avoid an infinitude of micro entity files, this script
also loads grouped XML Entity files, at some expected locations.

These grouped files are really normal XML files, correctly annotated
with XML namespaces used on the manual, so any individual exported entity
has correct and clean XML namespace annotations. These grouped entity
files are tracked normally by revcheck, but are not directly included
in manual.xml.in, as they only participate in general entity loading,
described above.

# Checks

Groped XML Entity files are annotated with an attribute named "translate",
that accepts the following values:

- "yes": these entities are expected to be translated or replaced;
- "no": these entities are expected not be translated or replaced;
- "delete": these entities should be deleted on sight.

The characteristics above are validated at the end of the script. Use the
--debug argument to also list the names of misused entities.

The "delete" value exists to make possible deleting entities from
doc-en while keeping translations building. To achieve this result,
move any recently deleted to a .ent file with translate="delete".

*/

const PARTIAL_IMPL = true; // For while XML Entities are not fully implanted in all languages

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );

Entities::truncateOutputFile();

$langs = [];
$debug = false;
$usage = in_array( '--help' , $argv ) || in_array( '-h' , $argv );

if ( count( $argv ) < 2 || $usage )
{
    print "\nUsage: {$argv[0]} langCode [langCode] [--debug]\n\n";
    if ( $usage )
        exit( 0 );
    else
        exit( 1 );
}
array_shift( $argv );
foreach( $argv as $arg )
    if ( $arg == "--debug" )
        $debug = true;
    else
        $langs[] = $arg;

if ( $debug )
    print "Running text-entities.ent in debug mode.\n";
else
    print "Running text-entities.ent... ";

foreach( $langs as $lang )
{
    $entDir = __DIR__ . "/../../$lang/entities";
    $refDir = __DIR__ . "/../../$lang/reference";

    loadDirEntities( $entDir );
    loadDirRecurse( $refDir );
    Entities::$countLanguages++;
}

Entities::writeOutputFile();
Entities::checkReplaces( $debug );

echo "done: generated " , Entities::$countTotalGenerated , " entities";
if ( Entities::$countUntranslated  > 0 )
    echo ", " , Entities::$countUntranslated , " untranslated";
if ( Entities::$countUniqueReplaced > 0 )
    echo ", " , Entities::$countUniqueReplaced , " unique replaced";
if ( Entities::$countRemoveReplaced  > 0 )
    echo ", " , Entities::$countRemoveReplaced , " remove replaced";
echo ".\n";

exit;

enum EntityCheck
{
    case Unique; // Expected once
    case Normal; // Expected used/translated
    case Remove; // Expected unused
}

class EntityData
{
    public function __construct(
        public string $path ,
        public string $name ,
        public string $text ) {}
}

class Entities
{
    private static string $filename = __DIR__ . "/../temp/entities.ent";

    private static array $merged = [];          // All EntityData, merged by name, no duplications
    private static array $unique = [];          // Any entity marked unique
    private static array $remove = [];          // Any entity marked deleted
    private static array $nameCount = [];       // Name / Count

    public static int $countLanguages = 0;      // For translated check
    public static int $countUntranslated = 0;
    public static int $countUniqueReplaced = 0;
    public static int $countRemoveReplaced = 0;
    public static int $countTotalGenerated = 0;

    static function put( string $path , string $name , string $text , bool $unique = false , bool $remove = false )
    {
        $entity = new EntityData( $path , $name , $text );
        Entities::$merged[ $name ] = $entity;

        if ( $unique )
            Entities::$unique[ $name ] = $name;

        if ( $remove )
            Entities::$remove[ $name ] = $name;

        if ( ! isset( Entities::$nameCount[ $name ] ) )
            Entities::$nameCount[ $name ] = 0;
        Entities::$nameCount[ $name ]++;
    }

    static function truncateOutputFile()
    {
        if ( file_exists( Entities::$filename ) )
            unlink( Entities::$filename );
        touch( Entities::$filename );
        Entities::$filename = realpath( Entities::$filename ); // only full paths on XML
    }

    static function writeOutputFile()
    {
        outputFiles( Entities::$filename , Entities::$merged );
    }

    static function checkReplaces( bool $debug )
    {
        Entities::$countTotalGenerated = count( Entities::$merged );
        Entities::$countUntranslated = 0;
        Entities::$countUniqueReplaced = 0;
        Entities::$countRemoveReplaced = 0;

        foreach( Entities::$merged as $name => $null )
        {
            $replaced = Entities::$nameCount[$name] - 1;
            $languages = Entities::$countLanguages;
            $expectedUnique = in_array( $name , Entities::$unique );
            $expectedRemoved  = in_array( $name , Entities::$remove );
            $expectedTranslated = ! ( $expectedUnique || $expectedRemoved );

            if ( $expectedUnique && $replaced != 0 )
            {
                Entities::$countUniqueReplaced++;
                if ( $debug )
                    print " Expected unique, replaced $replaced times:     $name\n";
            }

            if ( $expectedRemoved && $replaced != 0 )
            {
                Entities::$countRemoveReplaced++;
                if ( $debug )
                    print " Expected removed, replaced $replaced times:    $name\n";
            }

            if ( $expectedTranslated && $replaced != 1 && $languages != 1 )
            {
                Entities::$countUntranslated++;
                if ( $debug )
                    print " Expected translated, replaced $replaced times: $name\n";
            }
        }
    }
}

function loadDirEntities( string $dir )
{
    $dir = realpath( $dir );
    if ( $dir === false || ! is_dir( $dir ) )
    {
        if ( PARTIAL_IMPL )
        {
            print "(skip) ";
            return;
        }
        else
        {
            print "\n  Not a directory: $dir\n";
            exit( 1 );
        }
    }

    $files = scandir( $dir );
    foreach( $files as $file )
    {
        $path = realpath( "$dir/$file" );

        if ( str_starts_with( $file , '.' ) )
            continue;
        if ( is_dir( $path ) )
            continue;

        if ( str_ends_with( $path , ".xml" ) )
            loadEntitySingle( $path );

        if ( str_ends_with( $path , ".ent" ) )
            loadEntityGroup( $path );
    }
}

function loadDirRecurse( string $dir )
{
    $paths = scandir( $dir );
    foreach( $paths as $path )
    {
        if ( str_starts_with( $path , '.' ) )
            continue;

        $path = realpath( "$dir/$path" );

        if ( is_dir( $path ) )
            loadDirRecurse( $path );
        else
            if ( str_ends_with( $path , ".ent" ) )
                loadEntityGroup( $path );
    }
}

function loadEntityGroup( string $path )
{
    $path = realpath( $path );
    $text = file_get_contents( $path );
    $text = str_replace( "&" , "&amp;" , $text );

    $dom = new DOMDocument( '1.0' , 'utf8' );
    if ( ! $dom->loadXML( $text ) )
        die( "XML load failed for $path\n" );

    $unique = false;
    $remove = false;
    $value = $dom->documentElement->getAttribute("translate");
    switch ( $value )
    {
        case "no":
            $unique = true;
            break;
        case "delete":
        case "remove":
            $remove = true;
            break;
        default:
            print "\n Invalid translate attribute '$value' in '$path'.\n";
            exit( 1 );
    }

    $xpath = new DOMXPath( $dom );
    $list = $xpath->query( "/*/*" );

    foreach( $list as $ent )
    {
        $name = $ent->getAttribute( "name" );

        // Weird, namespace correting, DOMNodeList -> DOMDocumentFragment transform

        $other = new DOMDocument( '1.0' , 'utf8' );
        foreach( $ent->childNodes as $node )
            $other->appendChild( $other->importNode( $node , true ) );

        // Piecewise reconstruct fragment, without XML declarations or extra newlines

        $text = "";
        foreach( $other->childNodes as $node )
            $text .= $other->saveXML( $node );

        Entities::put( $path , $name , $text , $unique , $remove );
    }
}

function loadEntitySingle( string $path )
{
    $text = file_get_contents( $path );
    $info = pathinfo( $path );
    $name = $info["filename"];
    $frag = "<frag>$text</frag>";

    if ( trim( $text ) == "" )
    {
        print "\n  Empty entity '$name' on file '$path'.\n";
        print "\n  Should it be in a file with translate='remove'?\n";
        Entities::put( $path , $name , $text );
        return;
    }

    // Validate. Accepts only the error "Entity * not defined"

    $dom = new DOMDocument( '1.0' , 'utf8' );
    $dom->recover = true;
    $dom->resolveExternals = false;
    libxml_use_internal_errors( true );

    $xml = $dom->loadXML( $frag );
    $err = libxml_get_errors();
    libxml_clear_errors();

    foreach( $err as $item )
    {
        $msg = trim( $item->message );

        if ( $item->code == 26 )
            continue;
        if ( str_starts_with( $msg , "Entity '" ) && str_ends_with( $msg , "' not defined" ) )
            continue;

        print "\n  XML load failed for entity file:";
        print "\n   Path:  $path";
        print "\n   Error: $msg\n";
        return;
    }

    Entities::put( $path , $name , $text );
}

function outputFiles( string $filename , array $entities )
{
    $file = fopen( $filename , "w" );
    fputs( $file , "\n<!-- DO NOT COPY / DO NOT TRANSLATE -->" );
    fputs( $file , "\n<!-- Autogenerated by text-entities.php -->\n\n" );

    $sepFileDir = __DIR__ . "/../temp/text-entities/";

    if ( file_exists( $sepFileDir ) == false )
        mkdir( $sepFileDir , recursive: true );

    foreach( $entities as $name => $entity )
    {
        $name = $entity->name;
        $body = $entity->text;

        $quote = "'";
        $count = 0;

        if ( str_contains( $body , "'" ) )
        {
            $quote = '"';
            $count++;
        }
        if ( str_contains( $body , '"' ) )
        {
            $quote = "'";
            $count++;
        }

        if ( $count < 2 )
        {
            // Fast path for single or no quote:
            // entity body directly quoted on output file.

            fputs( $file , "<!ENTITY $name {$quote}{$body}{$quote}>\n\n" );
            continue;
        }

        // Slow path: entity body as an external file,
        // as to avoid (re)quotation hell.

        $path = $sepFileDir . "/{$entity->name}.xml";

        if ( file_exists( $path ) )
        {
            echo "\nDuplicated text-entity file: '{$path}'.\n";
            exit( 1 );
        }

        // realpath() only after file creation

        file_put_contents( $path , $body );
        $path = realpath( $path );
        fputs( $file , "<!ENTITY $name SYSTEM '{$path}'>\n\n" );
    }

    fclose( $file );
}
