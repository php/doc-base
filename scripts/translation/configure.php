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
 *  | Description: Generate cached data for revcheck and QA tools.         |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/lib/all.php';

if ( count( $argv ) < 2 || in_array( '--help' , $argv ) || in_array( '-h' , $argv ) )
{
    fwrite( STDERR , "Usage: {$argv[0]} [lang_dir]\n\n" );
    fwrite( STDERR , "See https://github.com/php/doc-base/tree/master/scripts/translation#readme for more info.\n" );
    return;
}

new RevcheckRun( 'en' , $argv[1] , true );
