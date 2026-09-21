<?php

declare(strict_types=1);

namespace PhpDoc\Dev\Command;

use PhpDoc\Dev\Environment\Environment;
use PhpDoc\Dev\Options;
use PhpDoc\Dev\Workspace;

final class PullCommand implements Command
{
    public function __construct(
        private readonly Workspace $workspace,
        private readonly Environment $environment,
    ) {
    }

    public function execute(Options $options): int
    {
        $mapNames = $this->environment->canMapDirectoryNames();

        if (!$this->workspace->ensureLangRepos($options->lang, $mapNames)) {
            return 1;
        }

        $this->workspace->pullSideRepos($options->lang, verbose: true);

        return 0;
    }
}
