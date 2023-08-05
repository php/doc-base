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
    public string $head = ""; // from vcs, source only, head hash, may be skipped
    public string $diff = ""; // from vcs, source only, diff hash, no skips
    public int    $date = 0 ; // from vcs, source only, date of head or diff commit

    public RevcheckStatus  $status; // target only
    public RevtagInfo|null $revtag; // target only

    function __construct( string $file , int $size )
    {
        $this->file = $file;
        $this->size = $size;
        $this->head = "";
        $this->diff = "";
        $this->date = 0;
        $this->status = RevcheckStatus::Untranslated;
        $this->revtag = null;
    }
}