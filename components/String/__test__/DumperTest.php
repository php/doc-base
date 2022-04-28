<?php

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
