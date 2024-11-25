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
    private int $slowPathCount = 0;

    function __construct( string $sourceDir , string $targetDir , bool $writeResults = false )
    {
        $this->sourceDir = $sourceDir;
        $this->targetDir = $targetDir;

        // Load respective file trees
        $this->sourceFiles = new RevcheckFileList( $sourceDir );
        $this->targetFiles = new RevcheckFileList( $targetDir );

        // Source files get info from version control
        GitLogParser::parseDir( $sourceDir , $this->sourceFiles );

        // Target files get info from revtags
        RevtagParser::parseDir( $targetDir , $this->targetFiles );

        // match and mix
        $this->parseTranslationXml();
        $this->calculateStatus();

        // fs output
        if ( $writeResults )
        {
            QaFileInfo::cacheSave( $this->qaList );
            $this->saveRevcheckData();
        }

        if ( $this->slowPathCount > 1000 )
            fprintf( STDERR , "Warn: Slow path called {$this->slowPathCount} times.\n" );
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

            $daysOld = ( strtotime( "now" ) - $source->date ) / 86400;
            $daysOld = (int)$daysOld;

            // TODO Make QA related state detect changes and autogenerate
            // TODO Move all QA related code outside of RevcheckRun
            {
                $sourceHash = $source->isSyncHash( $target->revtag->revision ) ? $source->hashDiff : $source->hashLast;
                $targetHash = $target->revtag->revision;

                $qaInfo = new QaFileInfo( $sourceHash , $targetHash , $this->sourceDir , $this->targetDir , $source->file , $daysOld );
                $this->qaList[ $source->file ] = $qaInfo;
            }

            // TranslatedOk

            if ( $target->revtag->status == "ready" && $source->isSyncHash( $target->revtag->revision ) )
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
                $this->addData( $target , $target->revtag );
            }
        }

        asort( $this->revData->fileDetail );
    }

    private function addData( RevcheckFileItem $info , RevtagInfo|null $revtag = null ) : void
    {
        $file = new RevcheckDataFile;

        $file->path = dirname( $info->file );
        $file->name = basename( $info->file );
        $file->size = $info->size;
        $file->days = floor( ( time() - $info->date ) / 86400 );
        $file->status = $info->status;
        $file->hashLast = $info->hashLast;
        $file->hashDiff = $info->hashDiff;

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
                    $this->slowPathCount++;
                    GitSlowUtils::parseAddsDels( $this->sourceDir , $file );
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
