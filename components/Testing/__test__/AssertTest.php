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
use PHPDoc\Internal\Testing\Assert;

class AssertTest extends Assert
{
    public function testAssertSame()
    {
        $this->assertSame('foo', 'foo');
        $this->assertSame(1, 1);
        $this->assertSame(1.2, 1.2);
        $this->assertSame(['foo'], ['foo']);
        $this->assertSame([0 => 'bar'], [0 => 'bar']);
        $this->assertSame([0 => 'bar'], ['0' => 'bar']);
        $this->assertSame($obj = new stdClass(), $obj);
        $this->assertSame($callable = fn () => 1, $callable);
    }

    public function testAssertNotSame()
    {
        $this->assertNotSame('foo', 'fo');
        $this->assertNotSame(1, 1.1);
        $this->assertNotSame([0 => 'foo'], [1 => 'foo']);
        $this->assertNotSame(['foo'], ['bar']);
        $this->assertNotSame([0 => 'foo'], [0 => 'bar']);
        $this->assertNotSame([0 => 1], [0 => '1']);
        $this->assertNotSame(new stdClass(), new stdClass());
        $this->assertNotSame(fn () => 1, fn () => 1);
    }

    public function testAssertIs()
    {
        $this->assertFalse(false);
        $this->assertTrue(true);
        $this->assertNull(null);
        $this->assertCount(2, [1, 2]);
        $this->assertEmpty([]);
        $this->assertEmpty('');
    }

    public function testAssertOfType(): void
    {
        $this->assertOfType('string', 'a string');
        $this->assertOfType('int', 1);
        $this->assertOfType('float', 1.1);
        $this->assertOfType('bool', true);
        $this->assertOfType('array', []);
        $this->assertOfType('AssertTest', $this);
    }

    public function testAssertInstanceOf()
    {
        $this->assertInstanceOf($this, self::class);
    }
}
