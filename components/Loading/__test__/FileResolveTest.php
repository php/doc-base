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
use PHPDoc\Internal\Loading\FileResolver;
use PHPDoc\Internal\Testing\Assert;

class FileResolveTest extends Assert
{
    private const FIXTURES = __DIR__.'/fixtures';

    public function testData()
    {
        $resolver = new FileResolver('foo');
        $this->assertSame('foo/', $resolver->getRoot());

        $resolver->setRoot('bar');
        $this->assertSame('bar/', $resolver->getRoot());

        $resolver->setPrefix('foo');
        $this->assertSame('foo', $resolver->getPrefix());

        $resolver->setSuffix('bar');
        $this->assertSame('bar', $resolver->getSuffix());
    }

    public function testHasValidName()
    {
        $resolver = $this->createResolver();

        $resolver->setPrefix('_a');
        $resolver->setSuffix('b_');

        $this->assertTrue($resolver->hasValidName('_a_b_'));
        $this->assertFalse($resolver->hasValidName('a_b_'));
        $this->assertFalse($resolver->hasValidName('_a_b'));
    }

    public function testIsValidPath()
    {
        $resolver = $this->createResolver();

        $resolver->excludes(['bar', 'barr']);
        $this->assertFalse($resolver->isValidPath('foo/bar/'));
        $this->assertFalse($resolver->isValidPath('bar'));
        $this->assertFalse($resolver->isValidPath('bar'));
        $this->assertFalse($resolver->isValidPath('bar'));

        foreach (['foo/bar', '/bar', 'bar/', '/bar/', '../bar/', '../bar/../bar'] as $testDir) {
            $this->assertFalse($resolver->isValidPath($testDir));
        }

        foreach (['foo/bar/..', 'foo/barr/..', 'foo/bar/../', 'foo/barr/../', 'foo/bar/../bar/../'] as $testDir) {
            $this->assertTrue($resolver->isValidPath($testDir));
        }
    }

    public function testLoad()
    {
        $resolver = new FileResolver(self::FIXTURES.'/baz');
        $this->assertCount(1, $resolved = $resolver->getResolved());
        $this->assertSame(self::FIXTURES.'/baz/a.txt', $resolved[0]);
    }

    public function testDeepLoad()
    {
        $resolver = new FileResolver(self::FIXTURES.'/foo');
        $resolver->excludes(['barr']);
        $this->assertCount(3, $resolved = $resolver->getResolved());
        // use in_array because file are not retrieve on same order depending on OS/Distribution
        $this->assertTrue(in_array(self::FIXTURES.'/foo/a.php', $resolved));
        $this->assertTrue(in_array(self::FIXTURES.'/foo/bar/a.txt', $resolved));
        $this->assertTrue(in_array(self::FIXTURES.'/foo/b.yml', $resolved));

        $resolver->setPrefix('a');
        // reinitialize data
        $resolver->resolve();
        $this->assertCount(2, $resolved = $resolver->getResolved());
        $this->assertTrue(in_array(self::FIXTURES.'/foo/a.php', $resolved));
        $this->assertTrue(in_array(self::FIXTURES.'/foo/bar/a.txt', $resolved));
        $this->assertFalse(in_array(self::FIXTURES.'/foo/bar/b.yml', $resolved));

        $resolver->setSuffix('.php');
        // reinitialize data
        $resolver->resolve();
        $this->assertCount(1, $resolved = $resolver->getResolved());
        $this->assertSame(self::FIXTURES.'/foo/a.php', $resolved[0]);
    }

    public function testExclude()
    {
        $resolver = $this->createResolver();
        $resolver->excludes(['bar', 'baz', 'foo']);

        // To be sure that at list one file exists in each excluded folders and the test is relevant
        $this->assertTrue(file_exists(self::FIXTURES.'/bar/a.php'));
        $this->assertTrue(file_exists(self::FIXTURES.'/baz/a.txt'));
        $this->assertTrue(file_exists(self::FIXTURES.'/foo/b.yml'));

        $this->assertCount(1, $resolved = $resolver->getResolved());
        $this->assertSame($resolved[0], self::FIXTURES.'/a.txt');
    }

    public function testDeepExclude()
    {
        $resolver = $this->createResolver();
        $resolver->excludes(['bar', 'baz', 'barr']);

        // To be sure that the excluded file in 'foo/bar/' exists and the test is relevant
        $this->assertTrue(file_exists(self::FIXTURES.'/foo/bar/a.txt'));

        $this->assertCount(3, $resolved = $resolver->getResolved());
        $this->assertTrue(in_array(self::FIXTURES.'/foo/a.php', $resolved));
        $this->assertTrue(in_array(self::FIXTURES.'/foo/b.yml', $resolved));
        $this->assertTrue(in_array(self::FIXTURES.'/a.txt', $resolved));
    }

    public function testRelativeFileAccess()
    {
        $resolver = $this->createResolver();
        $resolver->excludes(['bar']);

        $this->assertTrue(file_exists($foo = self::FIXTURES.'/foo/../bar/a.php'));
        $this->assertTrue(file_exists($bar = self::FIXTURES.'/foo/../bar/../bar/a.php'));
        $this->assertFalse($resolver->isValidFile(new SplFileInfo($foo)));
        $this->assertFalse($resolver->isValidFile(new SplFileInfo($bar)));

        $this->assertTrue($resolver->isValidFile(new SplFileInfo(self::FIXTURES.'/bar/../baz/a.txt')));
        $this->assertTrue($resolver->isValidFile(new SplFileInfo(self::FIXTURES.'/bar/../baz/../a.txt')));
    }

    private function createResolver(): FileResolver
    {
        return new FileResolver(self::FIXTURES);
    }
}
