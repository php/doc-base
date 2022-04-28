<?php

use PHPDoc\Internal\Testing\Assert;

class RealTest extends Assert
{
    public function testA()
    {
        $this->assertTrue(true);
    }

    public function testFailA()
    {
        $this->assertTrue(false, 'foo');
    }

    public function testFailB()
    {
        $this->assertTrue(false);
        $this->assertTrue(false);
    }

    public function skipTest()
    {
        $this->assertTrue(false);
    }
}
