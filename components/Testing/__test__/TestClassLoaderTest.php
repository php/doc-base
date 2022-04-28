<?php

use PHPDoc\Internal\Testing\Assert;
use PHPDoc\Internal\Testing\Loader\TestClassLoader;

class TestClassLoaderTest extends Assert
{
    public function testLoad()
    {
        $loader = $this->createLoader();
        $this->assertCount(4, $loader->getResources());
    }

    public function testExclude()
    {
        $loader = $this->createLoader();

        $this->assertCount(4, $loader->getResources());
    }

    public function testOnlyIn()
    {
        $loader = $this->createLoader();
        $loader->onlyIn(['a']);

        [$file, $class] = $loader->getResources()[0];

        $this->assertCount(1, $loader->getResources());
        $this->assertTrue(str_ends_with($file, 'FakeCTest.php'));
        $this->assertSame($class, 'FakeCTest');
    }

    public function testOnly()
    {
        $loader = $this->createLoader();
        $loader->loadFile(new SplFileInfo(__DIR__ . '/fixtures/a/__special_test__/FakeCTest.php'));

        [$file, $class] = $loader->getResources()[0];

        $this->assertCount(1, $loader->getResources());
        $this->assertTrue(str_ends_with($file, 'FakeCTest.php'));
        $this->assertSame($class, 'FakeCTest');
    }

    public function testFileValidity()
    {
        $loader = $this->createLoader();
        $this->assertFalse($loader->isValidResource(new SplFileInfo(__DIR__.'/fixtures/__special_test__/FakeCTest.ph')));

        $this->assertFalse($loader->isValidResource(new SplFileInfo(__DIR__ . '/fixtures/__special_test__/exclude/ExcludedTest.php')));
        $this->assertFalse($loader->isValidResource(new SplFileInfo(__DIR__.'/fixtures/a/NotIn__special_test__DirectoryTest.php')));

        $this->assertTrue($loader->isValidResource(new SplFileInfo(__DIR__ . '/fixtures/__special_test__/FakeTest.php')));
    }

    public function testRelativeFileAccess()
    {
        $loader = $this->createLoader();
        $this->assertTrue(file_exists($foo = __DIR__ . '/fixtures/a/__special_test__/../NotInValidTestDirectoryTest.php'));
        $this->assertTrue(file_exists($bar = __DIR__ . '/fixtures/a/__special_test__/../__special_test__/../NotInValidTestDirectoryTest.php'));
        $this->assertFalse($loader->isValidResource(new SplFileInfo($foo)));
        $this->assertFalse($loader->isValidResource(new SplFileInfo($bar)));

        $this->assertTrue(file_exists($foo = __DIR__ . '/fixtures/__special_test__/exclude/../FakeTest.php'));
        $this->assertTrue(file_exists($bar = __DIR__ . '/fixtures/__special_test__/exclude/../exclude/../FakeTest.php'));
        $this->assertTrue($loader->isValidResource(new SplFileInfo($foo)));
        $this->assertTrue($loader->isValidResource(new SplFileInfo($bar)));
    }

    private function createLoader(): TestClassLoader
    {
        $loader =  new TestClassLoader(__DIR__.'/fixtures', '__special_test__', 'Test');
        $loader->excludes(['exclude', 'fail']);

        return $loader;
    }
}
