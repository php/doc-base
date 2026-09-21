<?php

declare(strict_types=1);

namespace PhpDoc\Dev;

final class Options
{
    public string $lang = 'en';

    public int $port = 8080;

    public ?bool $docker = null; /** null = use Docker when available */

    public string $format = 'xhtml';

    public bool $assumeYes = false;

    /** @var list<string> */
    public array $args = [];
}
