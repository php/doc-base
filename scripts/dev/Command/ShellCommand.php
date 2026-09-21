<?php

declare(strict_types=1);

namespace PhpDoc\Dev\Command;

use PhpDoc\Dev\Environment\Environment;
use PhpDoc\Dev\Options;

final class ShellCommand implements Command
{
    public function __construct(private readonly Environment $environment)
    {
    }

    public function execute(Options $options): int
    {
        return $this->environment->shell($options->lang);
    }
}
