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
