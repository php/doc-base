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
 *  | Description: Calculate translation sync/diff status from two         |
 *  |              directories.                                            |
 *  +----------------------------------------------------------------------+
 */

require_once __DIR__ . '/all.php';

class RevcheckRun
{
    public string $sourceDir;
    public string $targetDir;
    public RevcheckFileList $sourceFiles;
    public RevcheckFileList $targetFiles;

    public array $filesOk = [];
    public array $filesOld = [];
    public array $filesRevtagProblem = [];
    public array $filesUntranslated = [];
    public array $filesNotInEn = [];
    public array $filesWip = [];

    public array $qaList = [];
    public RevcheckData $revData;

    function __construct( string $sourceDir , string $targetDir , bool $writeResults = false )
    {
        $this->sourceDir = $sourceDir;
        $this->targetDir = $targetDir;

        // load respective file tree
        $this->sourceFiles = new RevcheckFileList( $sourceDir );
        $this->targetFiles = new RevcheckFileList( $targetDir );

        // original files get info from version control
        GitLogParser::parseInto( $sourceDir , $this->sourceFiles );

        // translated files get info from file contents
        RevtagParser::parseInto( $targetDir , $this->targetFiles );

        // match and mix
        $this->parseTranslationXml();
        $this->calculateStatus();

        // fs output
        if ( $writeResults )
        {
            QaFileInfo::cacheSave( $this->qaList );
            $this->saveRevcheckData();
        }
    }

    private function calculateStatus()
    {
        // Most of status are marked $sourceFiles,
        // except NotInEnTree, that are marked on $targetFiles.
        // $revData contains all status

        foreach( $this->sourceFiles->iterator() as $source )
        {
            $target = $this->targetFiles->get( $source->file );

            // Untranslated

            if ( $target == null )
            {
                $source->status = RevcheckStatus::Untranslated;
                $this->filesUntranslated[] = $source;
                $this->addData( $source , null );
                continue;
            }

            // RevTagProblem

            if ( $target->revtag == null || strlen( $target->revtag->revision ) != 40 )
            {
                $source->status = RevcheckStatus::RevTagProblem;
                $this->filesRevtagProblem[] = $source;
                $this->addData( $source , $target->revtag );
                continue;
            }

            // Previous code compares uptodate on multiple hashs. The last hash or the last non-skipped hash.
            // See https://github.com/php/doc-base/blob/090ff07aa03c3e4ad7320a4ace9ffb6d5ede722f/scripts/revcheck.php#L374
            // and https://github.com/php/doc-base/blob/090ff07aa03c3e4ad7320a4ace9ffb6d5ede722f/scripts/revcheck.php#L392 .

            $sourceHsh1 = $source->head;
            $sourceHsh2 = $source->diff;
            $targetHash = $target->revtag->revision;

            $daysOld = ( strtotime( "now" ) - $source->date ) / 86400;
            $daysOld = (int)$daysOld;

            $qaInfo = new QaFileInfo( $sourceHsh1 , $targetHash , $this->sourceDir , $this->targetDir , $source->file , $daysOld );
            $this->qaList[ $source->file ] = $qaInfo;

            // TranslatedOk

            if ( $target->revtag->status == "ready" && ( $sourceHsh1 == $targetHash || $sourceHsh2 == $targetHash ) )
            {
                $source->status = RevcheckStatus::TranslatedOk;
                $this->filesOk[] = $source;
                $this->addData( $source , $target->revtag );
                continue;
            }

            // TranslatedOld
            // TranslatedWip

            if ( $target->revtag->status == "ready" )
            {
                if ( FIXED_SKIP_REVCHECK && $source->diff == "skip" && TestFixedHashMinusTwo( $source->file , $targetHash ) )
                {
                    $source->status = RevcheckStatus::TranslatedOk;
                    $this->filesOk[] = $source;
                    $this->addData( $source , $target->revtag );
                }
                else
                {
                    $source->status = RevcheckStatus::TranslatedOld;
                    $this->filesOld[] = $source;
                    $this->addData( $source , $target->revtag );
                }
            }
            else
            {
                $source->status = RevcheckStatus::TranslatedWip;
                $this->filesWip[] = $source;
                $this->addData( $source , $target->revtag );
            }
        }

        // NotInEnTree

        foreach( $this->targetFiles->iterator() as $target )
        {
            $source = $this->sourceFiles->get( $target->file );
            if ( $source == null )
            {
                $target->status = RevcheckStatus::NotInEnTree;
                $this->filesNotInEn[] = $target;
                $this->addData( $target , $target->revtag );
            }
        }

        asort( $this->revData->fileDetail );
    }

    private function addData( RevcheckFileInfo $info , RevtagInfo|null $revtag = null ) : void
    {
        $file = new RevcheckDataFile;

        $file->path = dirname( $info->file );
        $file->name = basename( $info->file );
        $file->size = $info->size;
        $file->days = floor( ( time() - $info->date ) / 86400 );
        $file->status = $info->status;
        $file->hashLast = $info->head;
        $file->hashDiff = $info->diff;

        $this->revData->addFile( $info->file , $file );

        if ( $revtag != null )
        {
            $file->hashRvtg = $revtag->revision;
            $file->maintainer = $revtag->maintainer;
            $file->completion = $revtag->status;

            $translator = $this->revData->getTranslator( $revtag->maintainer );

            switch( $info->status )
            {
                case RevcheckStatus::TranslatedOk:
                    $translator->countOk++;
                    break;
                case RevcheckStatus::TranslatedOld:
                    $translator->countOld++;
                    break;
                default:
                    $translator->countOther++;
                    break;
            }

            switch( $info->status ) // adds,dels
            {
                case RevcheckStatus::TranslatedOld:
                case RevcheckStatus::TranslatedWip:
                    GitDiffParser::parseAddsDels( $this->sourceDir , $file );
            }
        }
    }

    private function parseTranslationXml() : void
    {
        $this->revData = new RevcheckData;
        $this->revData->lang = $this->targetDir;
        $this->revData->date = date("r");

        $dom = XmlUtil::loadFile( $this->targetDir . '/translation.xml' );

        $tag = $dom->getElementsByTagName( 'intro' )[0] ?? null;
        if ( $tag == null )
            $intro = "No intro available for the {$this->targetDir} translation of the manual.";
        else
        {
            $intro = "";
            foreach( $tag->childNodes as $node )
                $intro .= $dom->saveXML( $node );
        }
        $this->revData->intro = $intro;

        $persons = $dom->getElementsByTagName( 'person' );
        foreach( $persons as $person )
        {
            $nick = $person->getAttribute( 'nick' );
            $translator = $this->revData->getTranslator( $nick );
            $translator->name = $person->getAttribute( 'name' );
            $translator->email = $person->getAttribute( 'email' );
            $translator->vcs = $person->getAttribute( 'vcs' ) ?? "";
        }
    }

    private function saveRevcheckData()
    {
        $json = json_encode( $this->revData , JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        file_put_contents( __DIR__ . "/../../../.revcheck.json" , $json );
    }
}

function TestFixedHashMinusTwo($filename, $hash) :bool
{
    assert( FIXED_SKIP_REVCHECK ); // if deleted, delete entire funciont.

    // See mentions of FIXED_SKIP_REVCHECK on all.php for an explanation

    $cwd = getcwd();
    chdir( 'en' );
    $hashes = explode ( "\n" , `git log -2 --format=%H -- {$filename}` );
    chdir( $cwd );
    return ( $hashes[1] == $hash ); // $trFile->hash
}
