<?php

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
require_once __DIR__ . '/RevcheckFileInfo.php';
require_once __DIR__ . '/RevcheckFileList.php';
require_once __DIR__ . '/RevcheckIgnore.php';
require_once __DIR__ . '/RevcheckRun.php';
require_once __DIR__ . '/RevtagParser.php';
require_once __DIR__ . '/XmlUtil.php';