<?php

use PHPDoc\Internal\Testing\Assert;
use PHPDoc\Internal\Testing\AssertInterface;
use PHPDoc\Internal\Testing\Loader\TestClassLoader;
use PHPDoc\Internal\Testing\Tester;

class TesterTest extends Assert
{
    public function testTesting()
    {
        $loader = new TestClassLoader(__DIR__.'/fixtures/', '__special_test__', 'Test');
        $loader->onlyIn(['real']);
        $loader->excludes(['fail']);
        $tester = new Tester($loader);
        $report = $tester->test();

        $this->assertSame(1, $report['nb_passed_test']);
        $this->assertSame(2, $report['nb_failed_test']);
        $this->assertSame(1, $report['nb_skipped']);
        $this->assertSame(3, $report['nb_failed_assertion']);
        $this->assertSame(1, $report['nb_passed_assertion']);

        $this->assertCount(2, $report['errors']);

        $failure = $report['errors'][0]['failures'][0];
        $this->assertSame(__DIR__.'/fixtures/real/__special_test__/RealTest.php', $failure['file']);
        $this->assertSame(14, $failure['line']);
        $this->assertSame('foo', $failure['user_message']);
        $this->assertSame('should be', $failure['reason']);
        $this->assertSame('false', $failure['given']);
        $this->assertSame('true', $failure['expected']);
    }

    public function testFileWithoutClass()
    {
        $loader = new TestClassLoader(__DIR__.'/fixtures/', '__special_test__', 'Test');
        $loader->onlyIn(['real/__special_test__/fail']);
        $tester = new Tester($loader);

        try {
            $tester->test();
        } catch (RuntimeException $e) {
            $this->assertSame('Class "NotAClassTest" does not exists', $e->getMessage());
        }
    }

    public function testClassDoesNotImplementsAssertInterface()
    {
        $loader = new TestClassLoader(__DIR__.'/fixtures/', '__special_test__', 'Test');
        $loader->loadFile(__DIR__.'/fixtures/real/__special_test__/fail/DoesNotImplementsAssertInterfaceTest.php');
        $tester = new Tester($loader);

        try {
            $tester->test();
        } catch (RuntimeException $e) {
            $this->assertSame(sprintf(
                'Class "%s" must implements "%s" interface or extends "%s"',
                DoesNotImplementsAssertInterfaceTest::class, AssertInterface::class, Assert::class
            ), $e->getMessage());
        }
    }
}
