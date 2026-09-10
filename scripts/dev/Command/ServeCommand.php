<?php

declare(strict_types=1);

namespace PhpDoc\Dev\Command;

use PhpDoc\Dev\Environment\Environment;
use PhpDoc\Dev\Options;
use PhpDoc\Dev\Workspace;

final class ServeCommand implements Command
{
    public function __construct(
        private readonly Workspace $workspace,
        private readonly Environment $environment,
    ) {
    }

    public function execute(Options $options): int
    {
        $lang = $options->lang;
        $output = $this->workspace->langDir($lang) . '/output';

        if (!is_dir($output)) {
            echo "Note: $output does not exist yet; run \"php phpdoc.php render xhtml --lang=$lang\" first.\n";
        }

        // PhD renders each format into its own subdirectory of output/.
        // Serve the chunked XHTML tree directly, so http://localhost:<port>/
        // lands on its index page instead of a 404.
        $subdir = is_dir("$output/php-chunked-xhtml") ? '/php-chunked-xhtml' : '';

        echo "Serving the $lang manual at http://localhost:{$options->port}/ (Ctrl-C to stop)\n";

        return $this->environment->serve($lang, $options->port, $subdir);
    }
}
