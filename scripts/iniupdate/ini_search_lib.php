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

function recurse($dirs, $search_macros = false) {
    global $array;

    $cfg_get = array();

    if (is_array($dirs)) {
        foreach($dirs as $dir)
            recurse_aux($dir, $search_macros);
    } else {
        recurse_aux($dirs, $search_macros);
    }

    /* insert only if the key doesn't exist, as will probably have
       more accurant data in $array than here */
    foreach($cfg_get as $entry) {
        if (!isset($array[$entry[0]]))
            $array[$entry[0]] = array($entry[1], 'PHP_INI_ALL');

    }

    uksort($array, 'strnatcasecmp');
}


// recurse through the dirs and do the 'dirty work'
function recurse_aux($dir, $search_macros) {
    global $array, $replace, $cfg_get;

    if (!$dh = opendir($dir)) {
        die ("couldn't open the specified dir ($dir)");
    }

    while (($file = readdir($dh)) !== false) {

        if($file == '.' || $file == '..') {
            continue;
        }

        $path = $dir . '/' .$file;

        if(is_dir($path)) {
            recurse($path);
        } else {
            $file = file_get_contents($path);

            /* delete comments */
            $file = preg_replace('@(//.*$)|(/\*.*\*/)@SmsU', '', $file);

            /* The MAGIC Regexp :) */
            if(preg_match_all('/(?:PHP|ZEND)_INI_(?:ENTRY(?:_EX)?|BOOLEAN)\s*\(\s*"([^"]+)"\s*,((?:".*"|[^,])+)\s*,\s*([^,]+)/S', $file, $matches)) {

                $count = count($matches[0]);
                for($i=0;$i<$count;$i++) {

                    $default = htmlspecialchars(trim($matches[2][$i]), ENT_NOQUOTES);

                    $permissions = preg_replace(array('/\s+/', '/ZEND/'), array('', 'PHP'), $matches[3][$i]);
                    $permissions =  ($permissions == 'PHP_INI_PERDIR|PHP_INI_SYSTEM' || $permissions == 'PHP_INI_SYSTEM|PHP_INI_PERDIR') ? 'PHP_INI_PERDIR' : $permissions;

                    $array[$matches[1][$i]] = array($default, $permissions);
                }

            } //end of the magic regex


            // find the nasty cfg_get_*() stuff
            if(preg_match_all('/cfg_get_([^(]+)\s*\(\s*"([^"]+)",\s*&([^\s=]+)\s*\)/S', $file, $match, PREG_SET_ORDER)) {

                foreach($match as $arr) {
                    preg_match('/(?:(FAILURE|SUCCESS)\s*==\s*)?'.preg_quote($arr[0]).'(?:\s*==\s*(FAILURE|SUCCESS))?(?:(?:.|[\r\n]){1,30}'.preg_quote($arr[3]).'\s*=\s*(.+);)?/', $file, $m);

                    if ($m[1] == 'FAILURE' || $m[2] == 'FAILURE') {
                        $cfg_get[] = array($arr[2], $arr[1] == 'string' ? $m[3] : '"'.$m[3].'"');

                    } else { //$m[1] == 'SUCCESS'
                        if ($arr[1] == 'string')
                            $cfg_get[] = array($arr[2], '""');
                        else
                            $cfg_get[] = array($arr[2], '"0"');
                    }
                } //foreach cfg_get_*()
            } //end of nasty cfg_get_*() regex


            /* search for C macros */
            if($search_macros && preg_match_all('/#\s*define\s+(\S{5,})[ \t]+(.+)/S', $file, $matches)) {
                $count = count($matches[0]);
                for($i=0;$i<$count;$i++) {
                    $replace[$matches[1][$i]] = rtrim($matches[2][$i]);
                }
            } // end of macros


        } //!is_dir()
    } //while() loop

    closedir($dh);
}
?>
