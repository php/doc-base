<?php

require_once __DIR__ . '/require.php';

enum RevcheckStatus :string
{
    case Untranslated      = 'Untranslated';
    case RevTagProblem     = 'RevTagProblem';
    case TranslatedOk      = 'TranslatedOk';
    case TranslatedOld     = 'TranslatedOld';
    case TranslatedWip     = 'TranslatedWip';
    case NotInEnTree       = 'NotInEnTree';
}

class RevcheckFileInfo
{
    public string $file = ""; // from fs
    public int    $size = 0 ; // from fs
    public string $hash = ""; // from vcs, source only
    public string $skip = ""; // from vcs, source only
    public int    $date = 0 ; // from vcs, source only
    public int    $days = 0 ; // derived

    public RevcheckStatus  $status; // target only
    public RevtagInfo|null $revtag; // target only

    function __construct( string $file , int $size )
    {
        $this->file = $file;
        $this->size = $size;
        $this->hash = "";
        $this->date = 0;
        $this->days = 0;
        $this->status = RevcheckStatus::Untranslated;
        $this->revtag = null;
    }
}