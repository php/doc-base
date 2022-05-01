<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 8                                                      |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2011 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.0 of the PHP license,       |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_0.txt.                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors:    Eugène d'Augier <eugene-augier@gmail.com>                |
  +----------------------------------------------------------------------+

  $Id$
*/
namespace PHPDoc\Internal\IO;

class Printer
{
    public static function cr(int $count = 1): void
    {
        echo str_repeat("\n", $count);
    }

    public static function print(string $message): void
    {
        echo Style::apply($message)."\n";
    }

    public static function printSuccess(string $message): void
    {
        echo Style::success($message)."\n";
    }

    public static function printInfo(string $message): void
    {
        echo Style::info($message)."\n";
    }

    public static function printWarning(string $message): void
    {
        echo Style::warning($message)."\n";
    }

    public static function printError(string $message): void
    {
        echo Style::error($message)."\n";
    }

    public static function printBgSuccess(string $message): void
    {
        echo Style::bgSuccess($message)."\n";
    }

    public static function printBgInfo(string $message): void
    {
        echo Style::bgInfo($message)."\n";
    }

    public static function printBgWarning(string $message): void
    {
        echo Style::bgWarning($message)."\n";
    }

    public static function printBgError(string $message): void
    {
        echo Style::bgError($message)."\n";
    }
}
