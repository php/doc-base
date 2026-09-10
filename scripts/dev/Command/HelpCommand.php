<?php

declare(strict_types=1);

namespace PhpDoc\Dev\Command;

use PhpDoc\Dev\Options;

final class HelpCommand implements Command
{
    public function execute(Options $options): int
    {
        echo <<<HELP
        Dev build tool for the PHP manual, for every language, with or without
        Docker. Missing sibling repositories (en, the translation, web-doc, and
        without Docker also phd/docbook-cs) are cloned automatically on first
        use.

        Usage:
          php phpdoc.php <command> [options] [extra arguments]

        Commands:
          pull           Clone missing sibling repositories and update existing ones
          configure      Assemble and validate the manual, without rendering
          render xhtml   configure + render the chunked XHTML manual to <lang>/output
          render php     configure + render the web (PHP) version to <lang>/output
          cs lint        Run docbook-cs; extra arguments are passed through (paths, --wide)
          cs fix         Same as cs lint, with --fix: rewrite violations that have fixers
          serve          Serve <lang>/output over HTTP
          serve web-doc  Run a local doc.php.net site from the web-doc checkout
          docker build   Build the Docker image
          docker shell   Interactive shell inside the container

        Options:
          --lang=XX    Language to operate on (default: en)
          --port=NNNN  Port for serve (default: 8080)
          --docker     Force Docker mode (default: used when available)
          --no-docker  Force local mode
          --yes, -y    Clone missing repositories without asking for confirmation

        Any other argument after the command is passed through: to configure.php
        for configure/render (e.g. --with-partial=book.datetime), and to
        docbook-cs for cs lint/cs fix (e.g. reference/datetime --wide).

        HELP;

        return 0;
    }
}
