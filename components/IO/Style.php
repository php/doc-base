<?php

namespace PHPDoc\Internal\IO;

class Style
{
    public const PRINTER_MASK = "\033[%s%s\033[0m";
    public const SUCCESS = "1;32m";
    public const INFO = "1;34m";
    public const WARNING = "1;33m";
    public const ERROR = "1;31m";
    public const BG_SUCCESS = "1;42m";
    public const BG_INFO = "1;44m";
    public const BG_WARNING = "1;43m";
    public const BG_ERROR = "1;41m";

    public const AVAILABLE_COLOR = [
        's' => self::SUCCESS,
        'i' => self::INFO,
        'w' => self::WARNING,
        'e' => self::ERROR,
        'bgs' => self::BG_SUCCESS,
        'bgi' => self::BG_INFO,
        'bgw' => self::BG_WARNING,
        'bge' => self::BG_ERROR,
    ];

    public static function apply(string $str): string
    {
        return preg_replace_callback('/<fc:(s|i|w|e|bgs|bgi|bgw|bge)>((?:(?!<).)+)+<\/fc>/', function ($matches) {
            [,$color, $text] = $matches;
            return sprintf(static::PRINTER_MASK, static::AVAILABLE_COLOR[$color], $text);
        }, $str);
    }

    public static function success(string $message): string
    {
        return sprintf(static::PRINTER_MASK, static::SUCCESS, $message);
    }

    public static function error(string $message): string
    {
        return sprintf(static::PRINTER_MASK, static::ERROR, $message);
    }

    public static function info(string $message): string
    {
        return sprintf(static::PRINTER_MASK, static::INFO, $message);
    }

    public static function warning(string $message): string
    {
        return sprintf(static::PRINTER_MASK, static::WARNING, $message);
    }

    public static function bgSuccess(string $message): string
    {
        return sprintf(static::PRINTER_MASK, static::BG_SUCCESS, $message);
    }

    public static function bgError(string $message): string
    {
        return sprintf(static::PRINTER_MASK, static::BG_ERROR, $message);
    }

    public static function bgInfo(string $message): string
    {
        return sprintf(static::PRINTER_MASK, static::BG_INFO, $message);
    }

    public static function bgWarning(string $message): string
    {
        return sprintf(static::PRINTER_MASK, static::BG_WARNING, $message);
    }
}
