<?php
/**
 *  +----------------------------------------------------------------------+
 *  | Copyright (c) 1997-2026 The PHP Group                                |
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
 *  | Description: Tell apart real XML errors from undefined entities.     |
 *  +----------------------------------------------------------------------+
 */

// No require of all.php here. This file is also used by scripts/broken.php,
// that is otherwise standalone, and does not need the revcheck library.

class XmlErrorFilter
{
    private static bool   $ready  = false;
    private static string $prefix = "";
    private static string $suffix = "";
    private static string $extra  = "";

    /**
     * Manual files are parsed one by one, without any DTD, so every entity
     * reference is reported as an error by libxml. Instead of hardcoding
     * message texts, that change between libxml versions, provoke the two
     * known messages and learn them at runtime.
     *
     * - "Entity '?' not defined"                 (entity inside elements)
     * - "Extra content at the end of the document" (entity outside elements)
     */
    private static function setup() : void
    {
        if ( XmlErrorFilter::$ready )
            return;

        $was = libxml_use_internal_errors( true );

        $doc = new DOMDocument();
        $doc->recover            = true;
        $doc->resolveExternals   = false;
        $doc->substituteEntities = false;

        // Setup runs lazily, on first use, so the error buffer is cleared
        // around the probes below: the probe errors must not leak into the
        // next document parsed, and any pending error must not be read as
        // a probe result. Callers always own a copy of their own errors
        // before reaching this class, so nothing of value is dropped here.

        libxml_clear_errors();

        $doc->loadXML( "<x>&ZZZ;</x>" );
        $message = trim( libxml_get_errors()[0]->message );
        $message = str_replace( "ZZZ" , "\f" , $message );
        [ XmlErrorFilter::$prefix , XmlErrorFilter::$suffix ] = explode( "\f" , $message );
        libxml_clear_errors();

        $doc->loadXML( "<x/>&ZZZ;" );
        XmlErrorFilter::$extra = trim( libxml_get_errors()[0]->message );
        libxml_clear_errors();

        libxml_use_internal_errors( $was );

        XmlErrorFilter::$ready = true;
    }

    /** An entity reference that no DTD was around to resolve. Expected, not an error. */
    public static function isUndefinedEntity( string $message ) : bool
    {
        XmlErrorFilter::setup();
        $message = trim( $message );
        return str_starts_with( $message , XmlErrorFilter::$prefix ) &&
               str_ends_with( $message , XmlErrorFilter::$suffix );
    }

    /** Usually an entity reference outside of any enclosing tag. Reported, with a hint. */
    public static function isExtraContent( string $message ) : bool
    {
        XmlErrorFilter::setup();
        return trim( $message ) == XmlErrorFilter::$extra;
    }

    /**
     * Drop undefined entity messages, keep everything else.
     *
     * @param LibXMLError[] $errors
     * @return LibXMLError[]
     */
    public static function filter( array $errors ) : array
    {
        if ( count( $errors ) == 0 ) // by far the most common case
            return [];

        XmlErrorFilter::setup();

        $prefix = XmlErrorFilter::$prefix;
        $suffix = XmlErrorFilter::$suffix;

        $ret = [];
        foreach( $errors as $error )
        {
            $message = trim( $error->message );
            if ( str_starts_with( $message , $prefix ) && str_ends_with( $message , $suffix ) )
                continue;
            $ret[] = $error;
        }
        return $ret;
    }
}
