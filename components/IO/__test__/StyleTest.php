<?php

use PHPDoc\Internal\IO\Style;
use PHPDoc\Internal\Testing\Assert;

class StyleTest extends Assert
{
    public function testStyle()
    {
        $this->assertSame("\033[1;32mfoo\033[0m", Style::success('foo'));
        $this->assertSame("\033[1;34mfoo\033[0m", Style::info('foo'));
        $this->assertSame("\033[1;33mfoo\033[0m", Style::warning('foo'));
        $this->assertSame("\033[1;31mfoo\033[0m", Style::error('foo'));
        $this->assertSame("\033[1;42mfoo\033[0m", Style::bgSuccess('foo'));
        $this->assertSame("\033[1;44mfoo\033[0m", Style::bgInfo('foo'));
        $this->assertSame("\033[1;43mfoo\033[0m", Style::bgWarning('foo'));
        $this->assertSame("\033[1;41mfoo\033[0m", Style::bgError('foo'));

        $expected = "
            \033[1;32mfoo\033[0m
            \033[1;34mfoo\033[0m
            \033[1;33mfoo\033[0m
            \033[1;31mfoo\033[0m
            \033[1;42mfoo\033[0m
            \033[1;44mfoo\033[0m
            \033[1;43mfoo\033[0m
            \033[1;41mfoo\033[0m
        ";

        $given = "
            <fc:s>foo</fc>
            <fc:i>foo</fc>
            <fc:w>foo</fc>
            <fc:e>foo</fc>
            <fc:bgs>foo</fc>
            <fc:bgi>foo</fc>
            <fc:bgw>foo</fc>
            <fc:bge>foo</fc>
        ";

        $this->assertSame($expected, Style::apply($given));
    }
}
