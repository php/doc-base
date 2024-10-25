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

// NOTE: This file MAY be used in more of one git repository in future.
// If it is the case, please make note of this in *both* places.

enum RevcheckStatus : string
{
    case TranslatedOk   = 'TranslatedOk';
    case TranslatedOld  = 'TranslatedOld';
    case TranslatedWip  = 'TranslatedWip';
    case Untranslated   = 'Untranslated';
    case RevTagProblem  = 'RevTagProblem';
    case NotInEnTree    = 'NotInEnTree';
}

class RevcheckData
{
    public $translators = array(); // RevcheckDataTranslator
    public $fileSummary = array(); // RevcheckStatus, int
    public $fileDetail  = array(); // RevcheckDataFile

    public function __construct()
    {
        foreach ( RevcheckStatus::cases() as $status )
            $this->$fileSummary[ $tatus ] = 0;
    }
}

class RevcheckDataTranslator
{
    public string $name;
    public string $email;
    public string $nick;

    public int $filesUpdate;
    public int $filesOld;
    public int $filesWip;
}

class RevcheckDataFile
{
    public string $path;
    public string $name;
    public int    $size;
    public int    $days;

    public RevcheckStatus $status;

    public string $lastHash; // The most recent commit hash, skipped or not
    public string $diffHash; // The most recent, non [skip-revcheck] commit hash
}
