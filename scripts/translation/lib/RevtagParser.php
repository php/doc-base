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
 *  | Description: Parse revision and credits from XML comments.           |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class RevtagInfo
{
    public string $revision = "";
    public string $maintainer = "";
    public string $status = "";
    public string $credits = "";
    public array  $errors = [];
}

class RevtagParser
{
    static function parseDir( string $lang , RevcheckFileList $list )
    {
        foreach( $list->iterator() as $entry )
            $entry->revtag = RevtagParser::parseFile( $lang . '/' . $entry->file );
    }

    public static function parseFile( string $filename ): RevtagInfo|null
    {
        $doc = XmlUtil::loadFile( $filename );
        $ret = new RevtagInfo;
        RevtagParser::parseNodeRecurse( $doc , $ret , $filename );
        return $ret;
    }

    public static function parseText( string $contents ): RevtagInfo|null
    {
        $doc = XmlUtil::loadText( $contents );
        $ret = new RevtagInfo;
        RevtagParser::parseNodeRecurse( $doc , $ret );
        return $ret;
    }

    public static function parseNodeRecurse( DOMNode $node , RevtagInfo $ret , $filename = "" )
    {
        if ( $node->nodeType == XML_COMMENT_NODE )
            RevtagParser::parseComment( $node , $ret , $filename );

        foreach( $node->childNodes as $child )
            RevtagParser::parseNodeRecurse( $child , $ret , $filename );
    }

    public static function parseComment( DOMNode $node , RevtagInfo $ret , $filename = "" )
    {
        $text = trim( $node->textContent );

        if ( str_starts_with( $text , "EN-" ) )
        {
            // /EN-Revision:\s*(\S+)\s*Maintainer:\s*(\S+)\s*Status:\s*(\S+)/       // restrict maintainer without spaces
            // /EN-Revision:\s*(\S+)\s*Maintainer:\s(.*?)\sStatus:\s*(\S+)/         // accepts maintainer with spaces

            $match = array();
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
            else
                $ret->errors[] = "No revtag.";
        }

        if ( str_starts_with( $text , "CREDITS:" ) )
        {
            $match = array();
            $regex = "/CREDITS:(.*)/";
            if ( preg_match( $regex , $text , $match ) )
            {
                $ret->credits = trim( $match[1] );

                if ( $ret->credits == "" )
                    $ret->errors[] = "Empty credits.";
            }
        }
    }
}
