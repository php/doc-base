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
 *  | Description: General data of a file in a documentation tree.         |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class RevcheckFileInfo
{
    public string $file = ""; // from fs
    public int    $size = 0 ; // from fs
    public string $head = ""; // from vcs, source only, head hash, may be skipped
    public string $diff = ""; // from vcs, source only, diff hash, no skips
    public int    $date = 0 ; // from vcs, source only, date of head or diff commit

    public RevcheckStatus  $status; // target only
    public RevtagInfo|null $revtag; // target only

    function __construct( string $file , int $size )
    {
        $this->file = $file;
        $this->size = $size;
        $this->head = "";
        $this->diff = "";
        $this->date = 0;
        $this->status = RevcheckStatus::Untranslated;
        $this->revtag = null;
    }
}
