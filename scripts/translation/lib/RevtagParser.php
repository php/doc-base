<?php /*
+----------------------------------------------------------------------+
| Copyright (c) 1997-2026 The PHP Group                                |
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
| Description: Parse revision and credits from XML comments.           |
+----------------------------------------------------------------------+
*/

require_once __DIR__ . '/all.php';

class RevtagInfo
{
    public string $revision = "";
    public string $maintainer = "";
    public string $status = "";
    public string $credits = "";
    public array  $errors = [];
    public bool   $doNotTranslate = false;
}

class RevtagParser
{
    static function parseDir( string $lang , RevcheckFileList $list )
    {
        foreach( $list->iterator() as $entry )
        {
            $contents = file_get_contents( $lang . '/' . $entry->file );

            // Everything here should be XML. The exception is the old
            // entities files still written as DTD fragments.

            if ( str_ends_with( $entry->file , '.ent' ) )
            if ( str_contains( $contents , '<!ENTITY' ) )
            {
                $entry->revtag = RevtagParser::parseFullText( $contents );
                continue;
            }

            $entry->revtag = RevtagParser::parseXmlText( $contents );

            // Files are parsed here anyway, so reuse the errors already
            // collected by XmlUtil, instead of loading everything again.

            $error = XmlUtil::$lastErrors[0] ?? null;
            if ( $error != null )
                $entry->xmlError = trim( $error->message ) . " [{$error->line}:{$error->column}]";
        }
    }

    public static function parseXmlText( string $contents ) : RevtagInfo
    {
        $ret = new RevtagInfo;

        $doc = XmlUtil::loadText( $contents );
        $xpath = new DOMXPath( $doc );

        // Revtags in XML comments

        $comments = $xpath->query( '//comment()' );
        foreach( $comments as $comment )
            RevtagParser::parseTagText( $comment->textContent , $ret );

        // do-not-translate mark in processing instructions

        $marks = $xpath->query( '//processing-instruction()' );
        foreach( $marks as $mark )
            if ( $mark->target == 'do-not-translate' )
                $ret->doNotTranslate = true;

        return $ret;
    }

    public static function parseFullText( string $text ) : RevtagInfo
    {
        $ret = new RevtagInfo;

        $match = [];
        $regex = '/<!--.*?-->/';
        preg_match_all( $regex , $text , $match );

        foreach ( $match[0] as $comment )
            RevtagParser::parseTagText( $comment , $ret );

        return $ret;
    }

    public static function parseTagText( string $text , RevtagInfo $ret )
    {
        // /EN-Revision:\s*(\S+)\s*Maintainer:\s*(\S+)\s*Status:\s*(\S+)/       // restrict maintainer without spaces
        // /EN-Revision:\s*(\S+)\s*Maintainer:\s(.*?)\sStatus:\s*(\S+)/         // accepts maintainer with spaces

        $match = [];
        $regex = "/EN-Revision:\s*(\S+)\s*Maintainer:\s(.*?)\sStatus:\s*(\S+)/";
        if ( preg_match( $regex , $text , $match ) )
        {
            $ret->revision = trim( $match[1] );
            $ret->maintainer = trim( $match[2] );
            $ret->status = trim( $match[3] );

            if ( $ret->revision != "" && strlen( $ret->revision ) != 40 )
                $ret->errors[] = "Wrong hash size: {$ret->revision}";
            if ( $ret->maintainer == "" )
                $ret->errors[] = "Empty maintainer.";
            if ( $ret->status == "" )
                $ret->errors[] = "Empty status.";
        }

        $match = [];
        $regex = "/CREDITS:(.*)/";
        if ( preg_match( $regex , $text , $match ) )
        {
            $ret->credits = trim( $match[1] );

            if ( $ret->credits == "" )
                $ret->errors[] = "Empty credits.";
        }
    }

    // public static function parseFile( string $filename ): RevtagInfo|null
    // {
    //     $doc = XmlUtil::loadFile( $filename );
    //     $ret = new RevtagInfo;
    //     RevtagParser::parseNodeRecurse( $doc , $ret , $filename );
    //     return $ret;
    // }

    // public static function parseText( string $contents ): RevtagInfo|null
    // {
    //     $doc = XmlUtil::loadText( $contents );
    //     $ret = new RevtagInfo;
    //     RevtagParser::parseNodeRecurse( $doc , $ret );
    //     return $ret;
    // }

    // public static function parseNodeRecurse( DOMNode $node , RevtagInfo $ret , $filename = "" )
    // {
    //     if ( $node->nodeType == XML_COMMENT_NODE )
    //         RevtagParser::parseComment( $node , $ret , $filename );

    //     foreach( $node->childNodes as $child )
    //         RevtagParser::parseNodeRecurse( $child , $ret , $filename );
    // }

    // public static function parseComment( DOMNode $node , RevtagInfo $ret , $filename = "" )
    // {
    //     $text = trim( $node->textContent );

    //     if ( str_starts_with( $text , "EN-" ) )
    //     {
    //         // /EN-Revision:\s*(\S+)\s*Maintainer:\s*(\S+)\s*Status:\s*(\S+)/       // restrict maintainer without spaces
    //         // /EN-Revision:\s*(\S+)\s*Maintainer:\s(.*?)\sStatus:\s*(\S+)/         // accepts maintainer with spaces

    //         $match = [];
    //         $regex = "/EN-Revision:\s*(\S+)\s*Maintainer:\s(.*?)\sStatus:\s*(\S+)/";
    //         if ( preg_match( $regex , $text , $match ) )
    //         {
    //             $ret->revision = trim( $match[1] );
    //             $ret->maintainer = trim( $match[2] );
    //             $ret->status = trim( $match[3] );

    //             if ( $ret->revision != "" && strlen( $ret->revision ) != 40 )
    //                 $ret->errors[] = "Wrong hash size: {$ret->revision}";
    //             if ( $ret->maintainer == "" )
    //                 $ret->errors[] = "Empty maintainer.";
    //             if ( $ret->status == "" )
    //                 $ret->errors[] = "Empty status.";
    //         }
    //         else
    //             $ret->errors[] = "No revtag.";
    //     }

    //     if ( str_starts_with( $text , "CREDITS:" ) )
    //     {
    //         $match = [];
    //         $regex = "/CREDITS:(.*)/";
    //         if ( preg_match( $regex , $text , $match ) )
    //         {
    //             $ret->credits = trim( $match[1] );

    //             if ( $ret->credits == "" )
    //                 $ret->errors[] = "Empty credits.";
    //         }
    //     }
    // }

}
