<?php

declare(strict_types=1);

namespace PhpDoc\Dev\Command;

use PhpDoc\Dev\Environment\Environment;
use PhpDoc\Dev\Options;
use PhpDoc\Dev\Workspace;

final class LintCommand implements Command
{
    public function __construct(
        private readonly Workspace $workspace,
        private readonly Environment $environment,
        private readonly ConfigureCommand $configure,
        private readonly bool $fix,
    ) {
    }

    public function execute(Options $options): int
    {
        $lang = $options->lang;

        if (!$this->workspace->ensureLangRepos($lang, $this->environment->canMapDirectoryNames())) {
            return 1;
        }

        if ($this->configure->execute($options) !== 0) {
            echo "\nconfigure reported problems (see above); linting anyway.\n\n";
        }

        $langdir = $this->workspace->langDir($lang);
        $args = $options->args;

        if ($this->fix) {
            array_unshift($args, '--fix');
        }

        if (!file_exists("$langdir/docbookcs.xml")) {
            $template = $this->workspace->getDocbookcsConfig();

            if ($template === null) {
                return 1;
            }

            $config = "$langdir/.docbookcs.dev.xml";
            file_put_contents($config, str_replace('@LANG@', $lang, $template));
            register_shutdown_function(static function () use ($config): void {
                @unlink($config);
            });
            array_unshift($args, '--config=.docbookcs.dev.xml');
        }

        return $this->environment->lint($lang, $args);
    }
}
