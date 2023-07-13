<?php

require_once __DIR__ . '/require_all.php';

/**
 * Calculate translation sync/diff status from two directories
 */
class RevcheckInfo
{
    public RevcheckFileList $sourceFiles;
    public RevcheckFileList $targetFiles;

    function __construct( $sourceDir , $targetDir )
    {
        // Load file tree
        $this->sourceFiles = new RevcheckFileList( $sourceDir );
        $this->targetFiles = new RevcheckFileList( $targetDir );

        // Source translation files get hashes from VCS
        GitLogParser::parseInto( $sourceDir , $this->sourceFiles );
    }
}