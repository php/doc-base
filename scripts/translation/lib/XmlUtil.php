<?php

require_once __DIR__ . '/all.php';

/**
 * Misc funcionality dealing with XML
 */

class XmlUtil
{
    public static function extractEntities( $filename )
    {
        $was = libxml_use_internal_errors( true );

        $doc = new DOMDocument();
        $doc->recover = true;
        $doc->resolveExternals = false;
        $doc->load( $filename );

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors( $was );

        $ret = array();
        foreach ($errors as $error)
        {
            if ( preg_match( "/Entity '(\S+)' not defined/" , $error->message , $matches ) )
                $ret[] = $matches[1];
        }
        return $ret;
    }

    public static function listNodeType( DOMNode $node , int $type )
    {
        $ret = array();
        XmlUtil::listNodeTypeRecurse( $node , $type , $ret );
        return $ret;
    }

    public static function listNodeTypeRecurse( DOMNode $node , int $type, array & $ret )
    {
        if ( $node->nodeType == $type )
            $ret[] = $node;
        foreach( $node->childNodes as $child )
            XmlUtil::listNodeTypeRecurse( $child , $type, $ret );
    }

    public static function loadFile( $filename ):DOMDocument
    {
        $contents = file_get_contents( $filename );
        return XmlUtil::loadText( $contents );
    }

    public static function loadText( $contents ):DOMDocument
    {
        $was = libxml_use_internal_errors( true );

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = true;
        $doc->recover            = true;
        $doc->resolveExternals   = false;
        $doc->substituteEntities = false;

        $doc->loadXML( $contents );

        libxml_clear_errors();
        libxml_use_internal_errors( $was );

        return $doc;
    }
}