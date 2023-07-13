<?php

require_once __DIR__ . '/require.php';

enum RevcheckStatus
{
    case Untranslated      ;//= 'Untranslated';
    case RevTagProblem     ;//= 'RevTagProblem';
    case TranslatedWip     ;//= 'TranslatedWip';
    case TranslatedOk      ;//= 'TranslatedOk';
    case TranslatedOld     ;//= 'TranslatedOld';
    case TranslatedCritial ;//= 'TranslatedCritial';
    case NotInEnTree       ;//= 'NotInEnTree';
}

class RevcheckFileInfo
{
    public string $file; // from fs
    public int    $size; // from fs
    public int    $date; // from vcs, source only
    public string $hash; // from vcs, source only

    public RevcheckStatus $status; // target only
    public RevtagInfo $revtag;     // target only

    function __construct( string $file , int $size )
    {
        $this->file = $file;
        $this->size = $size;
        $this->date = 0;
        $this->hash = "";
        $this->status = RevcheckStatus::Untranslated;
    }
}