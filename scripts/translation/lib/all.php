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
 *  | Description: Old style, require all file.                            |
 *  +----------------------------------------------------------------------+
 */

ini_set( 'display_errors' , 1 );
ini_set( 'display_startup_errors' , 1 );
error_reporting( E_ALL );

require_once __DIR__ . '/CacheFile.php';
require_once __DIR__ . '/CacheUtil.php';
require_once __DIR__ . '/GitDiffParser.php';
require_once __DIR__ . '/GitLogParser.php';
require_once __DIR__ . '/OutputIgnoreArgv.php';
require_once __DIR__ . '/OutputIgnoreBuffer.php';
require_once __DIR__ . '/QaFileInfo.php';
require_once __DIR__ . '/RevcheckData.php';
require_once __DIR__ . '/RevcheckFileInfo.php';
require_once __DIR__ . '/RevcheckFileList.php';
require_once __DIR__ . '/RevcheckIgnore.php';
require_once __DIR__ . '/RevcheckRun.php';
require_once __DIR__ . '/RevtagParser.php';
require_once __DIR__ . '/XmlUtil.php';
