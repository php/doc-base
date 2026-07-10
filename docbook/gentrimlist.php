<?php
// SPDX-License-Identifier: 0BSD
// © André L F S Bacci <ae#php.net>
/*

This script reads a RelaxNG XML file, and calculates all elements
that _not_ contain <text> contents, in all alternatives. That is,
the list shows all elements that can have all inter-element
whitespace removed, without affecting the expected parsing of the
XML document that follows the RelaxNG specification.

If informed a second XML file, the script will calculate all savings
that can be done by stripping these insignificant whitespace between
elements.

*/

$arg0 = array_shift( $argv ) ?? null;
$rngFile = array_shift( $argv ) ?? null;
$xmlFile = array_shift( $argv ) ?? null;

if ( $rngFile == null )
{
    print "Usage: '$argv0' rngFile [xmlFile]\n\n";
    return;
}

$doc = new DOMDocument();
if ( ! $doc->load( $rngFile , LIBXML_NOBLANKS ) )
    return;

$rng = rng_load_file( $doc );
$wsi = rng_list_elements_trim( $rng );
xml_trim_file( $xmlFile , $wsi );

exit ( 0 );

class RngMaps
{
    private array $defRef = []; // <define>s to <ref>s
    private array $defTxt = []; // <define>s with <text/>
    private array $tagDef = []; // <element>s to <define>s

    public function addDefToRef( string $defName , string $refName )
    {
        $this->defRef[ $defName ][ $refName ] = true;
    }

    public function addDefHasTxt( string $defName )
    {
        $this->defTxt[ $defName ] = true;
    }

    public function addTagToDef( string $defName , string $tagName )
    {
        $this->tagDef[ $defName ][ $tagName ] = true;
    }
}

function rng_load_file( DOMDocument $doc ) : RngMaps
{
    $rng = new RngMaps;
    rng_load_recurse( $rng , $doc->documentElement , "" );
    return $rng;
}

function rng_load_recurse( RngMaps $rng , DOMNode $node , string $defName , int $level = 1 )
{
    $pad = str_repeat( ' ' , $level );
    //echo "{$pad}{$node->nodeName}\n";

    switch( $node->nodeName )
    {
        case '#text';
            return;

        case 'attribute';
            return;

        case 'define':
            $defName = $node->getAttribute( 'name' );
            break;

        case 'element':
            $name = $node->getAttribute( 'name' );
            $rng->addTagToDef( $defName , $name );
            echo "{$pad} def {$defName} tag {$name}\n";
            break;

        case 'ref':
            $name = $node->getAttribute( 'name' );
            $rng->addDefToRef( $defName , $name );
            echo "{$pad} def {$defName} ref {$name}\n";
            break;

        case 'text':
            $rng->addDefHasTxt( $defName );
            echo "{$pad} def {$defName} TEXT\n";
            break;
    }

    foreach( $node->childNodes as $child )
        rng_load_recurse( $rng , $child , $defName , $level + 1 );
}

function rng_list_elements_trim( RngMaps $rng )
{
    // For each <define>, expand all <ref>s, to mark
    // any <define> that indirectly refers to <text>.


    // For each <element> check it it's <define> refers
    // to <text>.
}
