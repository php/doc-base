<?php

require_once __DIR__ . '/require.php';

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
    public array $filesOk;
    public array $filesOld;
    public array $filesCritical;
    public array $filesRevtagProblem;
    public array $filesUntranslated;
    public array $filesNotInEn;
    public array $filesWip;

    function __construct( $sourceDir , $targetDir )
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

        $this->calculateStatus();
    }

    private function calculateStatus()
    {
        // All status are marked in source files,
        // except notinen, that are marked on target.

        foreach( $this->sourceFiles->list as $source )
        {
            $target = $this->targetFiles->get( $source->file );
            $monthAgo = strtotime( '-1 month' );

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

            // TranslatedOk

            if ( $source->hash == $target->hash && $target->revtag->status == "ready" )
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
            // TranslatedCritial

            if ( $source->date > $monthAgo )
            {
                $source->status = RevcheckStatus::TranslatedOld;
                $this->filesOld[] = $source;
            }
            else
            {
                $source->status = RevcheckStatus::TranslatedCritial;
                $this->filesCritical[] = $source;
            }
        }

        // NotInEnTree

        foreach( $this->targetFiles->list as $target )
        {
            $sourcePath = $this->sourceDir . '/' . $target->file;
            if( is_file( $sourcePath ) == false )
            {
                $target->status = RevcheckStatus::NotInEnTree;
                $this->filesNotInEn[] = $target;
            }
        }
    }
}