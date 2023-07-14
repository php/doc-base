<?php

require_once __DIR__ . '/require.php';

/**
 * Calculate translation sync/diff status from two directories
 */
class RevcheckRun
{
    public RevcheckFileList $sourceFiles;
    public RevcheckFileList $targetFiles;

    function __construct( $sourceDir , $targetDir )
    {
        // Load file tree
        $this->sourceFiles = new RevcheckFileList( $sourceDir );
        $this->targetFiles = new RevcheckFileList( $targetDir );

        // Source files get hashes from VCS
        GitLogParser::parseInto( $sourceDir , $this->sourceFiles );

        // Translated files get info from revtags
        RevtagParser::parseInto( $targetDir , $this->targetFiles );
    }
}