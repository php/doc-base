<?php

declare(strict_types=1);

namespace PhpDoc\Dev\Command;

use PhpDoc\Dev\Options;

interface Command
{
    public function execute(Options $options): int;
}
