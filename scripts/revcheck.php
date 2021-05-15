#!/usr/bin/php -q
<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2021 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.0 of the PHP license,       |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_0.txt.                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors:    Thomas Sch�fbeck <tom@php.net>                           |
  |             Gabor Hojtsy <goba@php.net>                              |
  |             Mark Kronsbein <mk@php.net>                              |
  |             Jan Fabry <cheezy@php.net>                               |
  |             André L F S Bacci <ae@php.net>                           |
  +----------------------------------------------------------------------+
*/

if ( $argc != 2 )
{
    print <<<USAGE

  Check the revision of translated files against the actual english XML files
  and print statistics.

  Usage:
    {$argv[0]} [translation]

  [translation] must be a valid git checkout directory of a translation.

  Read more about revision comments and related functionality in the
  PHP Documentation Howto: http://doc.php.net/tutorial/


USAGE;
    exit;
}

// Initialization

set_time_limit( 0 );

$root = getcwd();
$lang = $argv[1];

$gitData = []; // filename lang hash,date

$intro = "No intro available for the {$lang} translation of the manual.";

$oldfiles = []; //path, name, size

$enFiles = populateFileTree( 'en' );
$trFiles = populateFileTree( $lang );
captureGitValues( 'en'  , $gitData );
captureGitValues( $lang , $gitData );

computeSyncStatus( $enFiles , $trFiles , $gitData , $lang );
$translators = computeTranslatorStatus( $lang, $enFiles, $trFiles );

print_html_all( $enFiles , $trFiles , $translators, $lang );

// Model
class OldFilesInfo
{
    public $path;
    public $name;
    public $size;

    public function getKey()
    {
        return trim( $this->path . '/' . $this->name , '/' );
    }
}

class FileStatusEnum
{
    const Untranslated      = 'Untranslated';
    const RevTagProblem     = 'RevTagProblem';
    const TranslatedWip     = 'TranslatedWip';
    const TranslatedOk      = 'TranslatedOk';
    const TranslatedOld     = 'TranslatedOld';
    const TranslatedCritial = 'TranslatedCritial';
    const NotInEnTree       = 'NotInEnTree';
}

class FileStatusInfo
{
    public $path;
    public $name;
    public $size;
    public $hash;
    public $date;
    public $syncStatus;
    public $maintainer;
    public $completion;
    public $credits;

    public function getKey()
    {
        return trim( $this->path . '/' . $this->name , '/' );
    }
}

class TranslatorInfo
{
    public $name;
    public $email;
    public $nick;
    public $vcs;

    public $files_uptodate;
    public $files_outdated;
    public $files_wip;
    public $files_sum;
    public $files_other;

    public function __construct() {
        $this->files_uptodate = 0;
        $this->files_outdated = 0;
        $this->files_wip = 0;
        $this->files_sum = 0;
        $this->files_other = 0;
    }

    public static function getKey( $fileStatus ) {
        switch ( $fileStatus ) {
            case FileStatusEnum::RevTagProblem:
            case FileStatusEnum::TranslatedOld:
            case FileStatusEnum::TranslatedCritial:
                return "files_outdated";
                break;
            case FileStatusEnum::TranslatedWip:
                return "files_wip";
                break;
            case FileStatusEnum::TranslatedOk:
                return "files_uptodate";
                break;
            default:
                return "files_other";
        }
    }
}

function populateFileTree( $lang )
{
    $dir = new \DirectoryIterator( $lang );
    if ( $dir === false )
    {
        print "$lang is not a directory.\n";
        exit;
    }
    $cwd = getcwd();
    $ret = array();
    chdir( $lang );
    populateFileTreeRecurse( $lang , "." , $ret );
    chdir( $cwd );
    return $ret;
}

function populateFileTreeRecurse( $lang , $path , & $output )
{
    global $oldfiles;
    $dir = new DirectoryIterator( $path );
    if ( $dir === false )
    {
        print "$path is not a directory.\n";
        exit;
    }
    $todoPaths = [];
    $trimPath = ltrim( $path , "./");
    foreach( $dir as $entry )
    {
        $filename = $entry->getFilename();
        if ( $filename[0] == '.' )
            continue;
        if ( substr( $filename , 0 , 9 ) == "entities." )
            continue;
        if ( $entry->isDir() )
        {
            $todoPaths[] = $path . '/' . $entry->getFilename();
            continue;
        }
        if ( $entry->isFile() )
        {
            $ignoredFileNames = [
                'README.md',
                'translation.xml',
                'readme.first',
                'license.xml',
                'extensions.xml',
                'versions.xml',
                'book.developer.xml',
                'contributors.ent',
                'contributors.xml',
                'README',
                'DO_NOT_TRANSLATE',
                'rsusi.txt',
                'missing-ids.xml',
                'license.xml',
                'translation.xml',
                'versions.xml',
            ];

            $ignoredDirectories = [
                'chmonly',
            ];

            if(
                in_array($trimPath, $ignoredDirectories, true)
                || in_array($filename, $ignoredFileNames, true)
                || (strpos($filename, 'entities.') === 0)
                || !in_array(substr($filename, -3), ['xml','ent'], true)
                || (substr($filename, -13) === 'PHPEditBackup')
                || ($trimPath === 'appendices' && (in_array($filename, ['reserved.constants.xml', 'extensions.xml'], true)))
            ) {
                continue;
            }
            $file = new FileStatusInfo;
            $file->path = $trimPath;
            $file->name = $filename;
            $file->size = filesize( $path . '/' . $filename );
            $file->syncStatus = null;
            if ( $lang != 'en' )
            {
                parseRevisionTag( $entry->getPathname() , $file );
                $path_en = '../en/' . $trimPath . '/' . $filename;
                if( !is_file($path_en) ) //notinen
                {
                    $oldfile = new OldFilesInfo;
                    $oldfile->path = $trimPath;
                    $oldfile->name = $filename;
                    $oldfile->size = $file->size < 1024 ? 1 : floor( $file->size / 1024 );
                    $oldfiles[ $oldfile->getKey() ] = $oldfile;
                 } else {
                    $output[ $file->getKey() ] = $file;
                 }
             } else {
                 $output[ $file->getKey() ] = $file;
             }
         }
    }
    sort( $todoPaths );
    foreach( $todoPaths as $path )
        populateFileTreeRecurse( $lang , $path , $output );
}

function parseRevisionTag( $filename , FileStatusInfo $file )
{
    $fp = fopen( $filename , "r" );
    $contents = fread( $fp , 1024 );
    fclose( $fp );

    // No match before the preg
    $match = array ();

    $regex = "'<!--\s*EN-Revision:\s*(.+)\s*Maintainer:\s*(.+)\s*Status:\s*(.+)\s*-->'U";
    if (preg_match ($regex , $contents , $match )) {
        $file->hash = trim( $match[1] );
        $file->maintainer = trim( $match[2] );
        $file->completion = trim( $match[3] );
    }
    if ( $file->hash == null or strlen( $file->hash ) != 40 or
         $file->maintainer == null or
         $file->completion == null )
         $file->syncStatus = FileStatusEnum::RevTagProblem;

    $regex = "/<!--\s*CREDITS:\s*(.+)\s*-->/U";
    $match = array();
    preg_match ( $regex , $contents , $match );
    if ( count( $match ) == 2 )
        $file->credits = str_replace( ' ' , '' , trim( $match[1] ) );
    else
        $file->credits = '';
}

function captureGitValues( $lang , & $output )
{
    $cwd = getcwd();
    chdir( $lang );
    $fp = popen( "git --no-pager log --name-only" , "r" );
    $hash = null;
    $date = null;
    $utct = new DateTimeZone( "UTC" );
    while ( ( $line = fgets( $fp ) ) !== false )
    {
        if ( substr( $line , 0 , 7 ) == "commit " )
        {
            $hash = trim( substr( $line , 7 ) );
            continue;
        }
        if ( strpos( $line , 'Date:' ) === 0 )
        {
            $date = trim( substr( $line , 5 ) );
            $date = DateTime::createFromFormat ( "D M d H:i:s Y T" , $date );
            $date->setTime( 0 , 0 );
            continue;
        }
        if ( trim( $line ) == "" )
            continue;
        if ( substr( $line , 0 , 4 ) == '    ' )
            continue;
        if ( strpos( $line , ': ' ) > 0 )
            continue;
        $filename = trim( $line );
        if ( isset( $output[$filename][$lang] ) )
            continue;
        if ( $lang == 'en' ) $output[$filename][$lang]['hash'] = $hash;
        $output[$filename][$lang]['date'] = $date;
    }
    pclose( $fp );
    chdir( $cwd );
}

function computeSyncStatus( $enFiles , $trFiles , $gitData , $lang )
{
    $now = new DateTime( 'now' );
    foreach( $trFiles as $filename => $trFile )
    {
        // notinen
        $path_en = 'en/' . $trFile->path . '/' . $trFile->name;
        if( !is_file($path_en) )
        {
            $trFile->syncStatus = FileStatusEnum::NotInEnTree;
            continue;
        }

    }
    foreach( $enFiles as $filename => $enFile )
    {
        if ( isset( $gitData[ $filename ]['en'] ) )
        {
            $enFile->hash = $gitData[ $filename ]['en']['hash'];
            $enFile->date = $gitData[ $filename ]['en']['date'];
        }
        else
            print "Warn: No hash for en/$filename\n";

        $trFile = isset( $trFiles[ $filename ] ) ? $trFiles[ $filename ] : null;

        if ( $trFile == null ) // Untranslated
        {
            $enFile->syncStatus = FileStatusEnum::Untranslated;
            continue;
        }
        else
        {
            if ( isset( $gitData[ $filename ][ $lang ] ) )
                $trFile->date = $gitData[ $filename ][ $lang ]['date'];
        }
        // TranslatedWip
        if ( $trFile->completion != null && $trFile->completion != "ready" )
        {
            $trFile->syncStatus = FileStatusEnum::TranslatedWip;
            continue;
        }
        // TranslatedOk
        // TranslatedOld
        // TranslatedCritial
        if ( $enFile->hash == $trFile->hash )
            $trFile->syncStatus = FileStatusEnum::TranslatedOk;
        elseif ( strlen( $trFile->hash ) == 40 )
        {
            $trFile->syncStatus = FileStatusEnum::TranslatedOld;
            if ( $enFile->date == null
              || $trFile->date == null
              || $now->diff( $enFile->date , true )->days > 30
              || $now->diff( $trFile->date , true )->days > 30 )
            {
                $trFile->syncStatus = FileStatusEnum::TranslatedCritial;
            }
        }
    }
}

function parse_attr_string ( $tags_attrs ) {
    $tag_attrs_processed = array();

    foreach($tags_attrs as $attrib_list) {
        preg_match_all("!(.+)=\\s*([\"'])\\s*(.+)\\2!U", $attrib_list, $attribs);

        $attrib_array = array();
        foreach ($attribs[1] as $num => $attrname) {
            $attrib_array[trim($attrname)] = trim($attribs[3][$num]);
        }

        $tag_attrs_processed[] = $attrib_array;
    }

    return $tag_attrs_processed;
}

function computeTranslatorStatus( $lang, $enFiles, $trFiles ) {
    global $intro;
    $translation_xml = getcwd() . "/" . $lang . "/translation.xml";
    if (!file_exists($translation_xml)) {
        return [];
    }

    $txml = join("", file($translation_xml));
    $txml = preg_replace("/\\s+/", " ", $txml);

    preg_match("!<intro>(.+)</intro>!s", $txml, $match);
    $intro = trim($match[1]);

    preg_match("!<\?xml(.+)\?>!U", $txml, $match);
    $xmlinfo = parse_attr_string($match);
    $output_charset = $xmlinfo[1]["encoding"];

    $pattern = "!<person(.+)/\\s?>!U";
    preg_match_all($pattern, $txml, $matches);
    $translators = parse_attr_string($matches[1]);

    $translatorInfos = [];
    $unknownInfo = new TranslatorInfo();
    $unknownInfo->nick = "unknown";
    $translatorInfos["unknown"] = $unknownInfo;

    foreach ($translators as $key => $translator) {
        $info = new TranslatorInfo();
        $info->name = $translator["name"];
        $info->email = $translator["email"];
        $info->nick = $translator["nick"];
        $info->vcs = $translator["vcs"];

        $translatorInfos[$info->nick] = $info;
    }

    foreach( $enFiles as $key => $enFile ) {
        $info_exists = false;
        if (array_key_exists($enFile->getKey(), $trFiles)) {
            $trFile = $trFiles[$enFile->getKey()];
            $statusKey = TranslatorInfo::getKey($trFile->syncStatus);
            if (array_key_exists($trFile->maintainer, $translatorInfos)) {
                $translatorInfos[$trFile->maintainer]->$statusKey++;
                $translatorInfos[$trFile->maintainer]->files_sum++;
                $info_exists = true;
            }
        }
        if (!$info_exists) {
            $translatorInfos["unknown"]->$statusKey++;
            $translatorInfos["unknown"]->files_sum++;
        }
    }

    return $translatorInfos;
}

// Output

function print_html_all( $enFiles , $trFiles , $translators , $lang )
{
    print_html_header( $lang );
    print_html_translators($translators , $enFiles);
    print_html_files( $enFiles , $trFiles , $lang );
    print_html_notinen();
    print_html_misstags( $enFiles, $trFiles, $lang );
    print_html_untranslated( $enFiles );
    print_html_footer();
}

function print_html_header( $lang )
{
    $date = date("r");
    print <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<style type="text/css">
body { margin:0px 0px 0px 0px; background-color:#F0F0F0; font-family: sans-serif; text-align: center; }
a { color: black; }
h1 { color: #FFFFFF; }
table { margin-left: auto; margin-right: auto; text-align: left; border-spacing: 1px; }
th { color: white; background-color: #666699; padding: 0.2em; text-align: center; vertical-align: middle; }
td { padding: 0.2em 0.3em; }
.oc { white-space: nowrap; overflow: hidden; max-width: 7em; }
.copy { margin:0; padding: 0; font-size:small; }
.copy:hover { text-transform: uppercase; }
.copy:active { background: aqua; font-weight: bold; }
.o { white-space: nowrap; overflow: hidden; max-width: 3em; }
.c { text-align: center; }
.r { text-align: right; }
.b { font-weight: bold; }
.white { color: white; }
.black { color: black; }
.bgblue { background-color: #666699;}
.bggray { background-color: #dcdcdc;}
.bggreen { background-color: #68d888;}
.bgorange { background-color: #f4a460;}
.bgred { background-color: #ff6347;}
.bgyellow { background-color: #eee8aa;}
</style>
</head>
<body>

<div id="header" style="background-color: #9999CC;">
<h1 style="margin: 0; padding: 0.5em;">Status of the translated PHP Manual</h1>
<p style="font-size: small; margin: 0; padding: 1em;">Generated: $date / Language: $lang</p>
</div>

HTML;
}


function print_html_menu($href)
{
    print <<<HTML

<a id="$href"/>
<p><a href="#intro">Introduction</a>
| <a href="#translators">Translators</a>
| <a href="#filesummary">File summary</a>
| <a href="#files">Outdated Files</a>
| <a href="#notinen">Not in EN tree</a>
| <a href="#misstags">Missing revision numbers</a>
| <a href="#untranslated">Untranslated files</a>
</p><p/>
HTML;
}

function print_html_translators( $translators , $enFiles )
{
    global $intro;
    if (count($translators) === 0) return;
    print_html_menu("intro");
    print <<<HTML
<table width="820" border="0" cellpadding="4" cellspacing="1" align="center">
 <tr><td>$intro</td></tr>
</table>
<p/>
<table id="translators" width="820" border="0" cellpadding="4" cellspacing="1" align="center">
  <tr class=blue>
    <th rowspan=2>Translator's name</th>
    <th rowspan=2>Contact email</th>
    <th rowspan=2>Nick</th>
    <th rowspan=2>V<br>C<br>S</th>
    <th colspan=4>Files maintained</th>
  </tr>
  <tr>
    <th style="color:#000000">upto-<br>date</th>
    <th style="color:#000000">old</th>
    <th style="color:#000000">wip</th>
    <th class="blue">sum</th>
  </tr>
HTML;
		  $files_uptodate = 0;
		  $files_outdated = 0;
		  $files_wip = 0;
		  $files_sum = 0;

    foreach( $translators as $key => $person )
    {
        if ($person->nick === "unknown") continue;

       $files_uptodate += $person->files_uptodate;
       $files_outdated += $person->files_outdated;
       $files_wip += $person->files_wip;
       $files_sum += $person->files_sum;
        print <<<HTML

<tr>
  <td>{$person->name}</td>
  <td>{$person->email}</td>
  <td>{$person->nick}</td>
  <td class=c>{$person->vcs}</td>

  <td class=c>{$person->files_uptodate}</td>
  <td class=c>{$person->files_outdated}</td>
  <td class=c>{$person->files_wip}</td>
  <td class=c>{$person->files_sum}</td>
</tr>

HTML;

    }
    print "</table>\n";

//FILE SUMMARY
    $count = 0;
    $files_untranslated = 0;
    foreach( $enFiles as $key => $en )
    {
        if ( $en->syncStatus == FileStatusEnum::Untranslated ) {
            $files_untranslated++;
        }
        $count++;
    }
    $files_uptodate_percent = number_format($files_uptodate * 100 / $count, 2 );
    $files_outdated_percent = number_format($files_outdated * 100 / $count, 2 );
    $files_wip_percent = number_format($files_wip * 100 / $count, 2 );
    $files_untranslated_percent = number_format($files_untranslated * 100 / $count, 2 );
    print_html_menu("filesummary");
    print <<<HTML
<table border="0" cellpadding="4" cellspacing="1" style="text-align:center;">
<tr>
  <th>File status type</th>
  <th>Number of files</th>
  <th>Percent of files</th>
</tr>
<tr>
  <td>Up to date files</td>
  <td>$files_uptodate</td>
  <td>$files_uptodate_percent%</td>
</tr>
<tr>
  <td>Outdated files</td>
  <td>$files_outdated</td>
  <td>$files_outdated_percent%</td>
</tr>
<tr>
  <td>Work in progress</td>
  <td>$files_wip</td>
  <td>$files_wip_percent%</td>
</tr>
<tr>
  <td>Files available for translation</td>
  <td>$files_untranslated</td>
  <td>$files_untranslated_percent%</td>
</tr>
<tr>
  <td class=b>Files total</td>
  <td class=b>$count</td>
  <td class=b>100%</td>
</tr></table><p/>
HTML;
}

function print_html_misstags( $enFiles, $trFiles, $lang )
{
    print_html_menu("misstags");

    $files_misstags = 0;
    foreach( $trFiles as $key => $tr )
    {
        if ( $tr->syncStatus == FileStatusEnum::RevTagProblem )
            $files_misstags++;
    }

    if ($files_misstags == 0)
    {
        echo '<p>Good, all files contain revision numbers.</p>';
    } else {
        print <<<HTML
<table border="0" cellpadding="3" cellspacing="1" style="text-align:center">';
<tr>
 <th rowspan="2">Files without EN-Revision number ($files_misstags files):</th>
 <th colspan="3">Sizes in kB</th>
</tr>';
<tr><th>en</th><th>$lang</th><th>diff</th></tr>';
HTML;

        $last_path = null;
        asort($trFiles);
        foreach ($trFiles as $key => $tr)
        {
            if ( $tr->syncStatus != FileStatusEnum::RevTagProblem )
               continue;

            $en = $enFiles[ $key ];

            if ( $last_path != $tr->path )
            {
                 $path = $tr->path == '' ? '/' : $tr->path;
                 echo "<tr><th colspan='4'>$path</th></tr>";
                 $last_path = $tr->path;
            }
             $diff = intval($en->size - $tr->size);
             echo "<tr class='bgorange'><td>{$tr->name}</td><td>{$en->size}</td><td>{$tr->size}</td><td>$diff</td></tr>";
        }
        echo '</table>';
    }
}

function print_html_untranslated($enFiles)
{
    $exists = false;
    $count = 0;
    foreach( $enFiles as $key => $en )
    {
        if ( $en->syncStatus == FileStatusEnum::Untranslated ) {
            $exists = true;
            $count++;
        }
    }

    if (!$exists) return;
    print_html_menu("untranslated");
    print <<<HTML
<table width="600" border="0" cellpadding="3" cellspacing="1" align="center">
 <tr>
  <th>Untranslated files ($count files):</th>
  <th>Commit hash</th>
  <th>kb</th>
 </tr>
HTML;

    $path = null;
    asort($enFiles);
    foreach( $enFiles as $key => $en )
    {
        if ( $en->syncStatus != FileStatusEnum::Untranslated )
            continue;
        if ( !preg_match( "/^.*\.xml\$/", $en->name )
        || $en->name === "versions.xml"
        || $en->name === "license.xml"
        || $en->name ===  "extensions.xml"
        || $en->name === "contributors.xml"
        || $en->name === "reserved.constants.xml" )
            continue;
        if ( $path !== $en->path )
        {
            $path = $en->path;
            $path2 = $path == '' ? '/' : $path;
            print " <tr><th class='blue' colspan='3'>$path2</th></tr>";
        }
        $size = $en->size < 1024 ? 1 : floor( $en->size / 1024 );

    print <<<HTML

 <tr class="bgorange">
  <td class="c">$en->name</td>
  <td class="c">$en->hash</td>
  <td class="c">$size</td>
 </tr>
HTML;
    }
    print "</table>\n";
}

function print_html_footer()
{
    print_html_menu("");
    print <<<HTML
<p/>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
  var clipboard = new ClipboardJS('.btn');
  clipboard.on('success', function (e) {
     console.log(e);
  });
  clipboard.on('error', function (e) {
     console.log(e);
  });
</script>
</body>
</html>
HTML;
}

function print_html_files( $enFiles , $trFiles , $lang )
{
        print_html_menu("files");
        print <<<HTML
<table>
 <tr>
  <th rowspan="2">Translated file</th>
  <th colspan="2">Hash</th>
  <th colspan="3">Size in kB</th>
  <th colspan="3">Age in days</th>
  <th rowspan="2">Maintainer</th>
  <th rowspan="2">Status</th>
 </tr>
 <tr>
  <th>en</th>
  <th>$lang</th>
  <th>en</th>
  <th>$lang</th>
  <th>diff</th>
  <th>en</th>
  <th>$lang</th>
  <th>diff</th>
 </tr>

HTML;

    $now = new DateTime( 'now' );
    $path = null;
    asort($trFiles);
    foreach( $trFiles as $key => $tr )
    {
        if ( $tr->syncStatus == FileStatusEnum::TranslatedOk )
            continue;
        if ( $tr->syncStatus == FileStatusEnum::RevTagProblem )
            continue;
        if ( $tr->syncStatus == FileStatusEnum::NotInEnTree )
            continue;
        $en = $enFiles[ $key ];
        if ( $en->syncStatus == FileStatusEnum::Untranslated )
              continue;

        if ( $path !== $en->path )
        {
            $path = $en->path;
            $path2 = $path == '' ? '/' : $path;
            print " <tr><th colspan='11' class='blue c'>$path2</th></tr>";
        }
        switch( $tr->syncStatus )
        {
            case FileStatusEnum::TranslatedOld:     $bg = 'bgyellow'; break;
            case FileStatusEnum::TranslatedCritial: $bg = 'bgred'   ; break;
            default:                                $bg = 'bggray'  ; break;
        }
        // GitHub web diff -- May not work with very old commits
        $kh = hash( 'sha256' , $key );
        $d1 = "https://github.com/php/doc-en/compare/{$tr->hash}..{$en->hash}#diff-{$kh}";
        // Local git diff  -- Always work
        $d2 = "(cd en; git diff {$tr->hash} {$en->hash} -- $key)";
        $d2 = htmlspecialchars( $d2 , ENT_QUOTES );
        // git.php.net     -- May not work with very recent commits
        $d3 = "https://git.php.net/?p=doc/en.git;a=blobdiff_plain;f=$key;hb={$en->hash};hpb={$tr->hash};"; // text
        $d4 = "https://git.php.net/?p=doc/en.git;a=blobdiff;f=$key;hb={$en->hash};hpb={$tr->hash};";       // html
        // And now, all the options
        $nm = "<a href='$d1' title='GitHub'>{$en->name}</a>"
            ." <button class='btn copy' data-clipboard-text='{$d2}' title='git diff command'>(c)</button>"
            ." <a href='$d3' title='git.php.net text'>(t)</a> "
            ." <a href='$d4' title='git.php.net html'>(h)</a>";
        if ( $en->syncStatus == FileStatusEnum::RevTagProblem )
            $nm = $en->name;
        $h1 = "<a href='http://git.php.net/?p=doc/en.git;a=blob;f=$key;hb={$en->hash}'>{$en->hash}</a>";
        $h2 = "<a href='http://git.php.net/?p=doc/en.git;a=blob;f=$key;hb={$tr->hash}'>{$tr->hash}</a>";
        $s1 = $en->size < 1024 ? 1 : floor( $en->size / 1024 );
        $s2 = $tr->size < 1024 ? 1 : floor( $tr->size / 1024 );
        $s3 = $s2 - $s1;
        $a1 = $now->diff( $en->date )->days;
        $a2 = $now->diff( $tr->date )->days;
        $a3 = $a2 - $a1;
        $ma = $tr->maintainer;
        $st = $tr->completion;
        print <<<HTML
 <tr class="$bg">
  <td class="l">$nm</td>
  <td class="oc">
    <button class="btn copy" data-clipboard-text="{$en->hash}">
      Copy
    </button>
    $h1
  </td>
  <td class="o">$h2</td>
  <td class="r">$s1</td>
  <td class="r">$s2</td>
  <td class="r">$s3</td>
  <td class="r">$a1</td>
  <td class="r">$a2</td>
  <td class="r">$a3</td>
  <td class="c">$ma</td>
  <td class="c">$st</td>
 </tr>
HTML;
    }
print "</table><p/>\n";
}

function print_html_notinen()
{
    global $oldfiles;
    print_html_menu("notinen");
    $exists = false;
    $count = 0;
    foreach( $oldfiles as $key => $en )
    {
        if ( $key == "{$en->path}/{$en->name}" ) {
            $exists = true;
            $count++;
        }
    }
    if (!$exists)
    {
         print "<p>Good, it seems that this translation doesn't contain any file which is not present in English tree.</p>\n";
     } else {
         print <<<HTML
<table>
 <tr>
  <th>Files which is not present in English tree.  ($count files)</th>
  <th>Size in kB</th>
 </tr>
HTML;
         $path = null;
         foreach( $oldfiles as $key => $en )
         {
              if ( $path !== $en->path )
              {
                   $path = $en->path;
                   print " <tr><th class='blue' colspan='2'>/$path</th></tr>";
              }
              print <<<HTML
 <tr class=bggray>
  <td class="c">$en->name</td>
  <td class="c">$en->size</td>
 </tr>
HTML;
         }
print "</table><p/>";
    }
}
