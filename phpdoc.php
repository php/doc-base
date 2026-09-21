#!/usr/bin/env php
<?php

/**
 * Dev build tool for the PHP manual, usable for every language
 * Run "php phpdoc.php help" for usage
 */

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'PhpDoc\\Dev\\')) {
        require __DIR__ . '/scripts/dev/' . str_replace('\\', '/', substr($class, 11)) . '.php';
    }
});

exit((new PhpDoc\Dev\Application(__DIR__))->run(array_slice($argv, 1)));
