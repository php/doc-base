<?php /*
// SPDX-License-Identifier: 0BSD
// © André L F S Bacci <ae#php.net>

This script reads a RelaxNG XML file, and calculates all elements
that _not_ contain <text/> contents, in all alternatives. That is,
the list shows all elements that can have all inter-element
whitespace removed, without affecting the expected parsing of the
XML document that follows the RelaxNG specification.

If informed a second XML file, the script will calculate all savings
that can be done by stripping these insignificant whitespace between
elements. */

$arg0 = array_shift( $argv ) ?? null;
$rngFile = array_shift( $argv ) ?? null;
$xmlFile = array_shift( $argv ) ?? null;

if ( $rngFile == null )
{
    print "Usage: '$argv0' rngFile [xmlFile]\n\n";
    return;
}

$list = generate_element_trim_list( $rngFile );

if ( $xmlFile == null )
{
    foreach( $list as $elem => $hasText )
        if ( ! $hasText )
            print "$elem\n";
    exit( 0 );
}
else
    xml_trim_stats( $xmlFile , $list );

exit( 0 );

function generate_element_trim_list( string $rngFilename ) : array
{
    $doc = new DOMDocument();
    if ( ! $doc->load( $rngFilename , LIBXML_NOBLANKS ) )
        throw new Exception( "XML load failed.\n" );

    // First, we get all elements definitions that directly
    // mentions <text/>, and also gather all <ref>s they refer.

    $elemText = [];
    $elemRefs = [];

    $xpath1 = new DOMXpath( $doc );
    $xpath2 = new DOMXpath( $doc );
    $xpath1->registerNamespace ( 'rng' , 'http://relaxng.org/ns/structure/1.0' );
    $xpath2->registerNamespace ( 'rng' , 'http://relaxng.org/ns/structure/1.0' );

    $list = $xpath1->query( '//rng:element' );
    foreach( $list as $elem )
    {
        $name = $elem->getAttribute( 'name' );
        if ( $name == '' )
            continue;

        $text = count ( $xpath2->query( './/rng:text' , $elem ) );
        $refs = $xpath2->query( './/rng:ref' , $elem );

        $elemText[ $name ] = $text;
        $elemRefs[ $name ] = [];

        foreach( $refs as $ref )
        {
            $refName = $ref->getAttribute( 'name' );
            $elemRefs[ $name ][] = $refName;
        }
    }

    unset( $xpath1 );
    unset( $xpath2 );

    // After all elements are collected, and directly textual elements
    // are marked, we can remove all <element>s, as they cannot influence
    // if a parent element is trimmable or not, and so that any <text/>
    // inside of a <element> cannot be found by XPaths, while exploring
    // the original <element>'s <ref>erences.

    $xpath3 = new DOMXpath( $doc );
    $xpath3->registerNamespace ( 'rng' , 'http://relaxng.org/ns/structure/1.0' );

    $todoDels = [];
    $dels = $xpath3->query( '//rng:element' );
    foreach( $dels as $del )
        array_push( $todoDels , $del );
    foreach( $todoDels as $del )
        $del->parentNode->removeChild( $del );

    // Then, we explore all references of all elements, for
    // indirect mentions of <text/>s.

    foreach( $elemText as $name => $text )
    {
        $text = element_references_contains_text( $doc , $name , $elemRefs[ $name ] );
        $elemText[ $name ] |= $text;
    }

    return $elemText;
}

function element_references_contains_text( DOMDocument $doc , string $elemName , array $refs ) : bool
{
    $ret = false;
    $doneRefs = [];
    $todoRefs = array_unique( $refs );

    $xpath = new DOMXpath( $doc );
    $xpath->registerNamespace ( 'rng' , 'http://relaxng.org/ns/structure/1.0' );

    while ( ( $refName = array_pop( $todoRefs ) ) != null )
    {
        $doneRefs[ $refName ] = true;

        $defs = $xpath->query( "//rng:define[@name='$refName']" );
        if ( $defs->count() != 1 )
            throw new Exception( "Unique define search failed for '$refName'." );
        $def = $defs[0];

        $text = count ( $xpath->query( './/rng:text' , $def ) );
        if ( $text )
            return true;

        $subRefs = $xpath->query( './/rng:ref' , $def );
        foreach( $subRefs as $subRef )
        {
            $subRefName = $subRef->getAttribute( 'name' );
            if ( isset( $doneRefs[ $subRefName ] ) )
                continue;
            $todoRefs[] = $subRefName;
        }
    }

    return false;
}

function xml_trim_stats( string $xmlFilename , array $elemText )
{
    $doc = new DOMDocument();
    if ( ! $doc->load( $xmlFilename ) )
        throw new Exception( "XML load failed.\n" );

    $stats = [];
    xml_trim_stats_enter( $doc->documentElement , $elemText, $stats );
    arsort( $stats );

    $total = 0;
    foreach( $stats as $elem => $trimSize )
    {
        print "$trimSize $elem\n";
        $total += $trimSize;
    }
    print "\ntotal $total\n";
}

function xml_trim_stats_enter( DOMNode $node , array $elemText , array & $stats , int $level = 0 )
{
    $name = $node->nodeName;
    $text = $elemText[ $name ] ?? true;

    if ( ! $text )
    {
        $size = 0;
        $dels = [];

        foreach( $node->childNodes as $child )
            if ( $child->nodeType == XML_TEXT_NODE )
                if ( trim( $child->nodeValue ) == '' )
                    $dels[] = $child;

        foreach( $dels as $del )
        {
            $size += strlen( $del->nodeValue );
            $del->parentNode->removeChild( $del );
        }

        if ( isset( $stats[ $name ] ) )
            $stats[ $name ] += $size;
        else
            $stats[ $name ] = $size;
    }

    foreach( $node->childNodes as $child )
        xml_trim_stats_enter( $child , $elemText , $stats , $level + 1 );
}