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

    // Separated lists
    public array $filesOk = [];
    public array $filesOld = [];
    public array $filesRevtagProblem = [];
    public array $filesUntranslated = [];
    public array $filesNotInEn = [];
    public array $filesWip = [];
    public array $qaList = [];

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

        if ( $writeResults )
            QaFileInfo::cacheSave( $this->qaList );
    }

    private function calculateStatus()
    {
        // All status are marked in source files,
        // except notinen, that are marked on target.

        foreach( $this->sourceFiles->iterator() as $source )
        {
            $target = $this->targetFiles->get( $source->file );

            // Untranslated

            if ( $target == null )
            {
                $source->status = RevcheckStatus::Untranslated;
                $this->filesUntranslated[] = $source;
                continue;
            }

            // RevTagProblem

            if ( $target->revtag == null || strlen( $target->revtag->revision ) != 40 )
            {
                $source->status = RevcheckStatus::RevTagProblem;
                $this->filesRevtagProblem[] = $source;
                continue;
            }

            // Translation compares ok from multiple hashs. The head hash or the last non-skiped hash.
            // See https://github.com/php/doc-base/blob/090ff07aa03c3e4ad7320a4ace9ffb6d5ede722f/scripts/revcheck.php#L374
            // and https://github.com/php/doc-base/blob/090ff07aa03c3e4ad7320a4ace9ffb6d5ede722f/scripts/revcheck.php#L392 .

            $sourceHash = $source->head;
            $targetHash = $target->revtag->revision;

            if ( $targetHash == $source->diff )
                $sourceHash = $source->diff;

            $daysOld = ( strtotime( "now" ) - $source->date ) / 86400;
            $daysOld = (int)$daysOld;

            $qaInfo = new QaFileInfo( $sourceHash , $targetHash , $this->sourceDir , $this->targetDir , $source->file , $daysOld );
            $this->qaList[ $source->file ] = $qaInfo;

            // TranslatedOk

            if ( $target->revtag->status == "ready" && $sourceHash == $targetHash )
            {
                $source->status = RevcheckStatus::TranslatedOk;
                $this->filesOk[] = $source;
                continue;
            }

            GitDiffParser::parseNumstatInto( $this->sourceDir , $source );

            // TranslatedWip

            if ( $target->revtag->status != "ready" )
            {
                $source->status = RevcheckStatus::TranslatedWip;
                $this->filesWip[] = $source;
                continue;
            }

            // TranslatedOld

            $source->days = $daysOld;
            $source->status = RevcheckStatus::TranslatedOld;
            $this->filesOld[] = $source;
        }

        // NotInEnTree

        foreach( $this->targetFiles->iterator() as $target )
        {
            $source = $this->sourceFiles->get( $target->file );
            if ( $source == null )
            {
                $target->status = RevcheckStatus::NotInEnTree;
                $this->filesNotInEn[] = $target;
            }
        }
    }
}
