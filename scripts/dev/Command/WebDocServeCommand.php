<?php

declare(strict_types=1);

namespace PhpDoc\Dev\Command;

use PhpDoc\Dev\Environment\Environment;
use PhpDoc\Dev\Options;
use PhpDoc\Dev\Workspace;

final class WebDocServeCommand implements Command
{
    public function __construct(
        private readonly Workspace $workspace,
        private readonly Environment $environment,
    ) {
    }

    public function execute(Options $options): int
    {
        $mapNames = $this->environment->canMapDirectoryNames();

        // The site needs en at request time (revcheck shells out to git in
        // it), web-doc itself, and web-shared inside it.
        if (!$this->workspace->ensureWebDocRepos() || !$this->workspace->ensureLang('en', $mapNames)) {
            return 1;
        }

        $sqlite = $this->workspace->webDocDir() . '/sqlite/status.sqlite';

        if (!is_file($sqlite)) {
            $generate = $this->workspace->confirm(
                "Generate $sqlite\n  (translation status data; parses git history and can take minutes)?"
            );

            if ($generate) {
                if ($this->generateDb($options, $mapNames) !== 0) {
                    return 1;
                }
            } else {
                echo "Note: serving without status.sqlite; translation status pages will be empty.\n";
            }
        }

        echo "Serving doc.php.net at http://localhost:{$options->port}/ (Ctrl-C to stop)\n";

        return $this->environment->serveWebDoc($options->port);
    }

    private function generateDb(Options $options, bool $mapNames): int
    {
        $langs = array_keys($this->workspace->translationCheckouts());

        if ($langs === [] && !$this->workspace->isBaseLang($options->lang)) {
            if (!$this->workspace->ensureLang($options->lang, $mapNames)) {
                return 1;
            }

            $langs = [$options->lang];
        }

        if ($langs === []) {
            echo "Note: no translation checkouts found; skipping status.sqlite "
                . "(clone one, or pass --lang=XX, and re-run).\n";
            return 0;
        }

        if ($this->workspace->isShallowRepo($this->workspace->langDir('en'))) {
            echo "Note: the en checkout has shallow git history; status data will be "
                . "incomplete (fix with: git -C " . $this->workspace->langDir('en') . " fetch --unshallow).\n";
        }

        $sqliteDir = $this->workspace->webDocDir() . '/sqlite';
        @mkdir($sqliteDir);
        @unlink("$sqliteDir/status.sqlite.new");

        echo 'Generating translation status data for: ' . implode(', ', $langs) . "\n";

        if ($this->environment->generateRevisionDb($langs) !== 0) {
            return 1;
        }

        // genrevdb can exit 0 even on failure, so also judge success by its
        // output file; renaming afterwards keeps a half-written database from
        // ever being published under the name the site reads.
        if (!is_file("$sqliteDir/status.sqlite.new")) {
            fwrite(STDERR, "error: generating status.sqlite failed.\n");
            return 1;
        }

        if (!rename("$sqliteDir/status.sqlite.new", "$sqliteDir/status.sqlite")) {
            fwrite(STDERR, "error: could not move status.sqlite.new into place.\n");
            return 1;
        }

        return 0;
    }
}
