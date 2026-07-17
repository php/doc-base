<?php
/**
 *  +----------------------------------------------------------------------+
 *  | Copyright (c) 1997-2023 The PHP Group                                |
 *  +----------------------------------------------------------------------+
 *  | This source file is subject to version 3.01 of the PHP license,      |
 *  | that is bundled with this package in the file LICENSE, and is        |
 *  | available through the world-wide-web at the following url:           |
 *  | https://www.php.net/license/3_01.txt.                                |
 *  | If you did not receive a copy of the PHP license and are unable to   |
 *  | obtain it through the world-wide-web, please send a note to          |
 *  | license@php.net, so we can mail you a copy immediately.              |
 *  +----------------------------------------------------------------------+
 *  | Authors:     André L F S Bacci <ae php.net>                          |
 *  +----------------------------------------------------------------------+
 *  | Description: Misc funcionality dealing with raw XML.                 |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

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

        $ret = [];
        foreach ($errors as $error)
        {
            if ( preg_match( "/Entity '(\S+)' not defined/" , $error->message , $matches ) )
                $ret[] = $matches[1];
        }
        return $ret;
    }

    public static function listNodeType( DOMNode $node , int $type )
    {
        $ret = [];
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
