<?php

require_once __DIR__ . '/require.php';

/**
 * Parse revision and credits in XML comments
 */

class RevtagInfo
{
    public string $revision = "";
    public string $maintainer = "";
    public string $status = "";
    public string $credits = "";
    public string $errors = "";
}

class RevtagParser
{
    static function parseInto( string $lang , RevcheckFileList & $list )
    {
        foreach( $list->list as $entry )
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

    public static function parseNodeRecurse( DOMNode $node , RevtagInfo & $ret , $filename = "" )
    {
        if ( $node->nodeType == XML_COMMENT_NODE )
            RevtagParser::parseComment( $node , $ret , $filename );

        foreach( $node->childNodes as $child )
            RevtagParser::parseNodeRecurse( $child , $ret , $filename );
    }

    public static function parseComment( DOMNode $node , RevtagInfo & $ret , $filename = "" )
    {
        $text = trim( $node->textContent );

        if ( str_starts_with( $text , "EN-" ) )
        {
            // /EN-Revision:\s*(\S+)\s*Maintainer:\s*(\S+)\s*Status:\s*(\S+)/ // restric maintainer with no spaces
            // /EN-Revision:\s*(\S+)\s*Maintainer:\s(.*?)\sStatus:\s*(\S+)/   // accepts maintainer with spaces

            $match = array();
            $regex = "/EN-Revision:\s*(\S+)\s*Maintainer:\s*(\S+)\s*Status:\s*(\S+)/";
            if ( preg_match( $regex , $text , $match ) )
            {
                $ret->revision = trim( $match[1] );
                $ret->maintainer = trim( $match[2] );
                $ret->status = trim( $match[3] );

                if ( $ret->revision != "" && strlen( $ret->revision ) != 40 )
                    $ret->errors .= "Wrong hash size: {$ret->revision}\n";
                if ( $ret->maintainer == "" )
                    $ret->errors .= "Empty maintainer.\n";
                if ( $ret->status != "" && strlen( $ret->revision ) != 40 )
                    $ret->errors .= "Empty status.\n";
            }
        }

        if ( str_starts_with( $text , "CRED" ) )
        {
            $match = array();
            $regex = "/CREDITS:\s*(\S+)/";
            if ( preg_match( $regex , $text , $match ) )
            {
                $ret->credits = $match[1];

                if ( $ret->credits != "" && strlen( $ret->revision ) != 40 )
                    $ret->errors .= "Empty credits.\n";
            }
        }
    }
}