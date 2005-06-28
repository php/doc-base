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

copy('ini_changelog.sqlite', 'backup.sqlite');

if (!$idx = sqlite_open('ini_changelog.sqlite', 0666, $error)) {
    die("Couldn't open the DB: $error");
}

$olddata = sqlite_fetch_all(sqlite_query($idx, 'SELECT * FROM changelog'), SQLITE_ASSOC);
$columns = array_keys($olddata[0]);
$columns_str = implode(',', $columns);

sqlite_query($idx, 'DROP TABLE changelog; VACUUM;');

// make a new table. this also fills the $tags array
include './make_db.php';

$sql = '';

foreach ($olddata as $row) {
    $sql .= "INSERT INTO changelog ($columns_str) VALUES (\"" . implode('", "', $row) . '");';
}

sqlite_query($idx, $sql);

$tmp = $tags;
$tags = array('php_4_cvs', 'php_5_cvs');

foreach($tmp as $tag) {
    if (!isset($columns[$tag])) {
        $tags[] = $tag;
    }
}

unset($tmp, $columns, $sql);

// finally recurse through the new PHP versions
include './insert_db.php';

sqlite_close($idx);

?>
