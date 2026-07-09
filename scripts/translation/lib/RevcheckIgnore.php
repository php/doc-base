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
 *  | Description: Files ignored on manual tree.                           |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class RevcheckIgnore
{
    public static function byName( $filename ) : bool
    {
        // Ignore dot files

        if ( $filename[0] == '.' )
            return true;

        // Ignore files other than xml assets

        if ( ( str_ends_with( $filename , '.xml' ) || str_ends_with( $filename , '.ent' ) ) == false )
            return true;

        // Only in translations

        if ( $filename == "translation.xml" )
            return true;

        // At least, do not ignore
        return false;
    }

    public static function byMark( $filename )
    {
        $contents = file_get_contents( $filename );
        return str_contains( $contents , '<?do-not-translate?>' );
    }
}
