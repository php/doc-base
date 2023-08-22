<?php

require_once __DIR__ . '/all.php';

/**
 * Calculate translation sync/diff status from two directories
 */

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

        foreach( $this->sourceFiles->list as $source )
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

            $target->hash = $target->revtag->revision;

            // translation compares ok from multiple hashs. See https://github.com/php/doc-base/commit/090ff07aa03c3e4ad7320a4ace9ffb6d5ede722f
            $wobblyOkHash = $source->head;        // L372
            if ( $target->hash == $source->diff ) // R391
                $wobblyOkHash = $source->diff;    // R392

            $daysOld = ( strtotime( "now" ) - $source->date ) / 86400;
            $daysOld = (int)$daysOld;

            $qaInfo = new QaFileInfo( $wobblyOkHash , $target->hash , $this->sourceDir , $this->targetDir , $source->file , $daysOld );
            $this->qaList[ $source->file ] = $qaInfo;

            // TranslatedOk

            if ( $target->revtag->status == "ready" && $wobblyOkHash == $target->hash )
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

        foreach( $this->targetFiles->list as $target )
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