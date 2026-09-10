<?php

declare(strict_types=1);

namespace PhpDoc\Dev\Command;

use PhpDoc\Dev\Environment\Environment;
use PhpDoc\Dev\Options;
use PhpDoc\Dev\Workspace;

final class ConfigureCommand implements Command
{
    public function __construct(
        private readonly Workspace $workspace,
        private readonly Environment $environment,
    ) {
    }

    public function execute(Options $options): int
    {
        $lang = $options->lang;

        if (!$this->workspace->ensureLangRepos($lang, $this->environment->canMapDirectoryNames())) {
            return 1;
        }

        $this->workspace->pullSideRepos($lang);

        $args = array_merge([
            ($this->workspace->isBaseLang($lang) ? '--with-base-lang=' : '--with-lang=') . $lang,
            '--enable-xml-details',
            '--disable-libxml-check',
            '--redirect-stderr-to-stdout',
        ], $options->args);

        return $this->environment->configure($lang, $args);
    }
}
