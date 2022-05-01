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
use PHPDoc\Internal\String\Dumper;
use PHPDoc\Internal\Testing\Assert;

class DumperTest extends Assert
{
    public function testDump()
    {
        $this->assertSame('"foo"', Dumper::dump('foo'));
        $this->assertSame('true', Dumper::dump(true));
        $this->assertSame('false', Dumper::dump(false));
        $this->assertSame('null', Dumper::dump(null));
        $this->assertSame('1', Dumper::dump(1));
        $this->assertSame('1.1', Dumper::dump(1.1));
        $this->assertSame('Object: DumperTest', Dumper::dump($this));

        $this->assertSame(<<<'EOD'
[
]
EOD, Dumper::dump([]));

        $this->assertSame(<<<'EOD'
[
  foo: [
      bar: [
          baz: 1
        ]
      0: true
      1: false
      2: null
      3: 1.1
      4: Object: DumperTest
    ]
]
EOD, Dumper::dump(['foo' => ['bar' => ['baz' => 1], true, false, null, 1.1, $this]]));
    }
}
