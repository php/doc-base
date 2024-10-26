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

    function __construct( string $sourceDir , string $targetDir , bool $writeResults = true )
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
        $this->revData = new RevcheckData;

        // All status are marked in source files,
        // except NotInEnTree, that are marked on target.

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
                $this->addData( $source , null );
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

            GitDiffParser::parseNumstatInto( $this->sourceDir , $source );

            if ( $target->revtag->status == "ready" )
            {
                $source->status = RevcheckStatus::TranslatedOld;
                $this->filesOld[] = $source;
                $this->addData( $source , $target->revtag );
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
                $this->addData( $target );
            }
        }
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
            $translator = $this->revData->getTranslator( $revtag->maintainer );

            switch( $info->status )
            {
                case RevcheckStatus::TranslatedOk:
                    $translator->filesUpdate++;
                    break;
                case RevcheckStatus::TranslatedOld:
                    $translator->filesOld++;
                    break;
                default:
                    $translator->filesWip++;
                    break;
            }
        }
    }

    private function parseTranslationXml() : void
    {
        $xml = XmlUtil::loadFile( $this->targetDir . '/translation.xml' );
        $persons = $xml->getElementsByTagName( 'person' );

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
        $this->parseTranslationXml();
    }
}
