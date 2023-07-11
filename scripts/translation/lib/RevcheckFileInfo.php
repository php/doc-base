<?php

require_once __DIR__ . '/require_all.php';

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
    public string $path;
    public string $name;
    public int $size;
    public DateTime $date;
    public string $key;

    public RevcheckStatus $syncStatus;

    function __construct( string $path , string $name , int $size , $date = new DateTime('@0') )
    {
        $this->path = $path;
        $this->name = $name;
        $this->size = $size;
        $this->date = $date;
        $this->key = ltrim( $path . '/' . $name , '/' );
        $this->syncStatus = RevcheckStatus::Untranslated;
    }
}