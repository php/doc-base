<?php

declare(strict_types=1);

namespace PhpDoc\Dev;

use PhpDoc\Dev\Command\BuildCommand;
use PhpDoc\Dev\Command\ConfigureCommand;
use PhpDoc\Dev\Command\HelpCommand;
use PhpDoc\Dev\Command\LintCommand;
use PhpDoc\Dev\Command\PullCommand;
use PhpDoc\Dev\Command\RenderCommand;
use PhpDoc\Dev\Command\ServeCommand;
use PhpDoc\Dev\Command\ShellCommand;
use PhpDoc\Dev\Command\WebDocServeCommand;
use PhpDoc\Dev\Environment\DockerEnvironment;
use PhpDoc\Dev\Environment\LocalEnvironment;

final class Application
{
    public function __construct(private readonly string $basedir)
    {
    }

    /**
     * @param list<string> $args Command line arguments, without argv[0].
     */
    public function run(array $args): int
    {
        $options = new Options();
        $command = $this->parse($args, $options);

        if ($command === null) {
            return 1;
        }

        if ($command === 'help') {
            return (new HelpCommand())->execute($options);
        }

        $subcommand = null;

        if ($command === 'docker') {
            $subcommand = array_shift($options->args);

            if (!in_array($subcommand, ['build', 'shell'], true)) {
                fwrite(STDERR, "Usage: php phpdoc.php docker <build|shell> (see: php phpdoc.php help)\n");
                return 1;
            }

            $options->docker = true;
        }

        if ($command === 'render') {
            $subcommand = array_shift($options->args);

            if (!in_array($subcommand, ['xhtml', 'php'], true)) {
                fwrite(STDERR, "Usage: php phpdoc.php render <xhtml|php> (see: php phpdoc.php help)\n");
                return 1;
            }

            $options->format = $subcommand;
        }

        // "serve" takes an optional subject: plain serve shows the rendered
        // manual, "serve web-doc" runs the doc.php.net site.
        if ($command === 'serve' && ($options->args[0] ?? null) === 'web-doc') {
            array_shift($options->args);
            $subcommand = 'web-doc';
        }

        if ($command === 'cs') {
            $subcommand = array_shift($options->args);

            if (!in_array($subcommand, ['lint', 'fix'], true)) {
                fwrite(STDERR, "Usage: php phpdoc.php cs <lint|fix> (see: php phpdoc.php help)\n");
                return 1;
            }
        }

        $runner = new ProcessRunner();
        $dockerAvailable = $runner->runQuiet(['docker', 'version', '--format', '{{.Server.Version}}']) === 0;

        if ($options->docker === true && !$dockerAvailable) {
            fwrite(STDERR, "error: Docker requested but the docker command is not available.\n");
            return 1;
        }

        $workspace = new Workspace($this->basedir, $runner, $options->assumeYes);
        $environment = ($options->docker ?? $dockerAvailable)
            ? new DockerEnvironment($workspace, $runner)
            : new LocalEnvironment($workspace, $runner);

        $configure = new ConfigureCommand($workspace, $environment);

        switch ($command) {
            case 'pull':
                return (new PullCommand($workspace, $environment))->execute($options);
            case 'configure':
                return $configure->execute($options);
            case 'render':
                return (new RenderCommand($workspace, $environment, $configure))->execute($options);
            case 'cs':
                return (new LintCommand($workspace, $environment, $configure, fix: $subcommand === 'fix'))
                    ->execute($options);
            case 'serve':
                return $subcommand === 'web-doc'
                    ? (new WebDocServeCommand($workspace, $environment))->execute($options)
                    : (new ServeCommand($workspace, $environment))->execute($options);
            case 'docker':
                return $subcommand === 'build'
                    ? (new BuildCommand($environment))->execute($options)
                    : (new ShellCommand($environment))->execute($options);
        }

        fwrite(STDERR, "Unknown command: $command (see: php phpdoc.php help)\n");

        return 1;
    }

    /** @param list<string> $args */
    private function parse(array $args, Options $options): ?string
    {
        $command = null;

        foreach ($args as $arg) {
            if (preg_match('/^--lang=(.+)$/', $arg, $m)) {
                $options->lang = $m[1];
                continue;
            }

            if (preg_match('/^--port=(\d+)$/', $arg, $m)) {
                $options->port = (int) $m[1];
                continue;
            }

            if ($arg === '--docker') {
                $options->docker = true;
                continue;
            }

            if ($arg === '--no-docker') {
                $options->docker = false;
                continue;
            }

            if ($arg === '--yes' || $arg === '-y') {
                $options->assumeYes = true;
                continue;
            }

            if ($command === null) {
                if ($arg[0] !== '-') {
                    $command = $arg;
                    continue;
                }

                if ($arg === '-h' || $arg === '--help') {
                    $command = 'help';
                    continue;
                }

                $fileName = $_SERVER['SCRIPT_FILENAME'];
                fwrite(STDERR, "Unknown option: $arg (see: php $fileName help)\n");
                return null;
            }

            $options->args[] = $arg;
        }

        return $command ?? 'help';
    }
}
