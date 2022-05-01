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
use PHPDoc\Internal\String\Writer;
use PHPDoc\Internal\Testing\Assert;

class WriterTest extends Assert
{
    public function testWrite()
    {
        $this->assertEmpty((new Writer())->text());
        $this->assertSame("\n", (new Writer())->cr()->text());
        $this->assertSame('foo', (new Writer())->write('foo')->text());
        $this->assertSame("\nfoo", (new Writer())->writeLine('foo')->text());
        $this->assertSame("\nfoo: bar", (new Writer())->writePair('foo', 'bar')->text());
        $this->assertSame("foo: bar", (new Writer())->writePair('foo', 'bar', false)->text());
    }
}
