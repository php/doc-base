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

class Writer
{
    private string $text = '';

    public function write(string $line): self
    {
        $this->text .= $line;

        return $this;
    }

    public function writeLine(string $line): self
    {
        $this->cr();
        $this->text .= $line;

        return $this;
    }

    public function writePair($key, $value, $cr = true): self
    {
        if ($cr) {
            $this->cr();
        }
        $this->text .= $key.': '.$value;

        return $this;
    }

    /**
     * Carriage return: write a blank line
     */
    public function cr(): self
    {
        $this->text .= "\n";

        return $this;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function echo(): void
    {
        $this->cr();

        echo $this->text;
    }
}
