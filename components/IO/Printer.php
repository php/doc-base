<?php

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
