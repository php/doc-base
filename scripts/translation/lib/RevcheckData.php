<?php

require_once __DIR__ . '/require_all.php';

/**
 * Calculate translation sync/diff status from two directories
 */
class RevcheckData
{
    public RevcheckFileList $sourceFiles;
    public RevcheckFileList $targetFiles;

    function __construct( $sourceDir , $targetDir )
    {
        $this->sourceFiles = new RevcheckFileList( $sourceDir );
        $this->targetFiles = new RevcheckFileList( $targetDir );
    }
}