<?php

namespace PHPDoc\Internal\String;

use InvalidArgumentException;

class Dumper
{
    public static function dump($item): string
    {
        return static::toString($item);
    }

    private static function toString($item, int $tab = 0): string
    {
        return match (get_debug_type($item)) {
            'string' => '"'.$item.'"',
            'int', 'float' => $item,
            'null' => 'null',
            'bool' => $item ? 'true' : 'false',
            'array' => static::arrayToString($item, $tab + 2),
            $item::class => 'Object: '.$item::class,
            default => throw new InvalidArgumentException(sprintf('Use of invalid type "%s"', $item)),
        };
    }

    private static function arrayToString(array $arr, int $tab = 0): string
    {
        $str = "[\n";
        foreach ($arr as $key => $value) {
            $str .= sprintf(
                "%s%s: %s\n",
                str_repeat(' ', $tab),
                $key,
                static::toString($value, $tab + 2)
            );
        }

        $tabs = str_repeat(' ', $tab - 2);

        return $str.$tabs."]";
    }
}
