<?php

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
