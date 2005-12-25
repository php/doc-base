<?php
/*
  +----------------------------------------------------------------------+
  | ini doc settings updater                                             |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2005 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.0 of the PHP license,       |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_0.txt.                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors:    Nuno Lopes <nlopess@php.net>                             |
  +----------------------------------------------------------------------+
*/

require_once './cvs-versions.php';
$db_open = isset($idx) ? true : false;

if (!$db_open && !$idx = sqlite_open('ini_changelog.sqlite', 0666, $error)) {
    die("Couldn't create the DB: $error");
}

$sql = 'CREATE TABLE changelog (
name TEXT PRIMARY KEY,';

foreach($tags as $tag) {
    $sql .= "$tag TEXT,";
}

$sql = substr($sql, 0, -1) . ');';

sqlite_query($idx, $sql);

if (!$db_open) {
    sqlite_close($idx);
}

?>
