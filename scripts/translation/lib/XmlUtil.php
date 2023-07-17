<?php

require_once __DIR__ . '/require.php';

/**
 * Misc funcionality dealing with XML
 */

class XmlUtil
{
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
        $was = libxml_use_internal_errors( true ); // do not print warnings

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