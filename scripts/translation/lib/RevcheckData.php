<?php
/**
 *  +----------------------------------------------------------------------+
 *  | Copyright (c) 1997-2024 The PHP Group                                |
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
 *  | Description: DTO for serialization of revcheck data.                 |
 *  +----------------------------------------------------------------------+
 */

enum RevcheckStatus : string
{
    case TranslatedOk  = 'TranslatedOk';
    case TranslatedOld = 'TranslatedOld';
    case TranslatedWip = 'TranslatedWip';
    case RevTagProblem = 'RevTagProblem';
    case NotInEnTree   = 'NotInEnTree';
    case Untranslated  = 'Untranslated';
}

class RevcheckData
{
    public string $lang = "";
    public string $date = "";
    public string $intro = "";
    public $translators  = array(); // nick => RevcheckDataTranslator
    public $fileSummary  = array(); // RevcheckStatus => int
    public $fileDetail   = array(); // filename => RevcheckDataFile

    public function __construct()
    {
        foreach ( RevcheckStatus::cases() as $status )
            $this->fileSummary[ $status->value ] = 0;
    }

    public function addFile( string $key , RevcheckDataFile $file )
    {
        $this->fileDetail[ $key ] = $file;
        $this->fileSummary[ $file->status->value ]++;
    }

    public function getTranslator( string $nick )
    {
        $translator = $this->translators[ $nick ] ?? null;
        if ( $translator == null )
        {
            $translator = new RevcheckDataTranslator();
            $translator->nick = $nick;
            $this->translators[ $nick ] = $translator;
        }
        return $translator;
    }

    public function getSummaryLabels() : array
    {
        $ret[ RevcheckStatus::TranslatedOk->value  ] = "Up to date files";
        $ret[ RevcheckStatus::TranslatedOld->value ] = "Outdated files";
        $ret[ RevcheckStatus::TranslatedWip->value ] = "Work in progress";
        $ret[ RevcheckStatus::RevTagProblem->value ] = "Revision tag missing/problem";
        $ret[ RevcheckStatus::NotInEnTree->value   ] = "Not in EN tree";
        $ret[ RevcheckStatus::Untranslated->value  ] = "Available for translation";
        return $ret;
    }
}

class RevcheckDataTranslator
{
    public string $name  = "";
    public string $email = "";
    public string $nick  = "";
    public string $vcs   = "";

    public int $countOk    = 0;
    public int $countOld   = 0;
    public int $countOther = 0;
}

class RevcheckDataFile
{
    public string $path;
    public string $name;
    public int    $size;
    public int    $days;
    public int    $adds = 0;
    public int    $dels = 0;

    public RevcheckStatus $status;
    public string $maintainer = "";
    public string $completion = "";

    public string $hashLast;      // The most recent commit hash, skipped or not
    public string $hashDiff;      // The most recent, non [skip-revcheck] commit hash
    public string $hashRvtg = ""; // Revtag hash, if any
}
