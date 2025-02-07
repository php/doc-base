<?php /*
+----------------------------------------------------------------------+
| Copyright (c) 1997-2025 The PHP Group                                |
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

# Description

Loads XML fragments bodies[1] in DOM, while tolerating and not dropping
undefined entities references.

[1] https://www.w3.org/TR/xml-fragment/#d1e1332                       */

class XmlFrag
{
    static function listNodes( DOMNode $node , int $type )
    {
        $ret = array();
        XmlFrag::listNodesRecurse( $node , $type , $ret );
        return $ret;
    }

    static function listNodesRecurse( DOMNode $node , int $type, array & $ret )
    {
        if ( $node->nodeType == $type )
            $ret[] = $node;
        foreach( $node->childNodes as $child )
            XmlFrag::listNodesRecurse( $child , $type, $ret );
    }

    static function loadXmlFragmentFile( string $filename )
    {
        $contents = file_get_contents( $filename );

        [ $doc , $ent , $err ] = XmlFrag::loadXmlFragmentText( $contents , "" );

        if ( count( $err ) == 0 )
            return [ $doc , $ent , $err ];

        $dtd = "<?xml version='1.0' encoding='utf-8'?>\n<!DOCTYPE frag [\n";
        foreach ( $ent as $e )
            $dtd .= "<!ENTITY $e ''>\n";
        $dtd .= "]>\n";

        [ $doc , $ign , $err ] = XmlFrag::loadXmlFragmentText( $contents , $dtd );

        return [ $doc , $ent , $err ];
    }

    static function loadXmlFragmentText( string $contents , string $dtd )
    {
        if ( str_starts_with( ltrim( $contents ) , '<?xml' ) )
        {
            $pos1 = strpos( $contents , '<?xml' );
            $pos2 = strpos( $contents , '?>' , $pos1 );
            $contents = substr( $contents , $pos2 +2 );
        }

        $contents = $dtd . "<frag>" . $contents . "</frag>";

        $doc = new DOMDocument();
        $doc->recover            = true;
        $doc->resolveExternals   = false;
        $doc->substituteEntities = false;

        $was = libxml_use_internal_errors( true );

        $doc->loadXML( $contents );
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors( $was );

        static $prefix = "", $suffix = "", $extra = "";
        if ( $prefix == "" )
            XmlFrag::setupErrors( $prefix , $suffix , $extra );

        $ent = [];
        $err = [];

        foreach( $errors as $error )
        {
            $message = trim( $error->message );

            if ( str_starts_with( $message , $prefix ) && str_ends_with( $message , $suffix ) )
            {
                $entity = $message;
                $entity = str_replace( $prefix , "" , $entity );
                $entity = str_replace( $suffix , "" , $entity );
                $ent[] = $entity;
            }

            $err[] = $message;
        }

        $fragment = $doc->createDocumentFragment();
        foreach( $doc->documentElement->childNodes as $node )
            $fragment->append( $node->cloneNode( true ) );

        $doc->removeChild( $doc->documentElement );
        $doc->appendChild( $fragment );

        return [ $doc , $ent , $err ];
    }

    static function setupErrors( string & $prefix , string & $suffix , string & $extra )
    {
        /*
        Undefined entities references generate TWO different error messages on
        some versions of libxml:

        - "Entity '?' not defined" (for entity inside elements)
        - "Extra content at the end of the document" (entity outside elements)
        */

        $inside = "<x>&ZZZ;</x>";
        $outside = "<x/>&ZZZ;";

        $doc = new DOMDocument();
        $doc->recover            = true;
        $doc->resolveExternals   = false;
        $doc->substituteEntities = false;

        $was = libxml_use_internal_errors( true );

        // prefix, suffix

        $doc->loadXML( $inside );
        $message = trim( libxml_get_errors()[0]->message );
        [ $prefix , $suffix ] = explode( "ZZZ" , $message );
        libxml_clear_errors();

        // extra

        $doc->loadXML( $outside );
        $extra = trim( libxml_get_errors()[0]->message );
        libxml_clear_errors();

        if ( strpos( $extra, "ZZZ" ) !== false )
            throw new Exception( "Unexpected error message." );

        libxml_use_internal_errors( $was );
    }
}
