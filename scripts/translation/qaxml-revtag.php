<?php /*
+----------------------------------------------------------------------+
| Copyright (c) 1997-2025 The PHP Group                                |
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

# Description

Inspect revision tag usage inside XML files.                           */

require_once __DIR__ . '/libqa/all.php';
require_once __DIR__ . '/lib/RevtagParser.php';

$argv   = new ArgvParser( $argv );
$ignore = new OutputIgnore( $argv ); // may exit.
$ignore->appendIgnoreCommands = false;
$argv->complete();

$list   = SyncFileList::load();

foreach ( $list as $file )
{
    $target = $file->targetDir . '/' . $file->file;
    $revtag = RevtagParser::parseFile( $target );

    if ( count( $revtag->errors ) == 0 )
        continue;

    print "# qaxml.r: $target\n";
    foreach( $revtag->errors as $error )
        print " $error\n";
    print "\n";
}
