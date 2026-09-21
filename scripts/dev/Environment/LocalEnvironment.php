<?php

declare(strict_types=1);

namespace PhpDoc\Dev\Environment;

use PhpDoc\Dev\ProcessRunner;
use PhpDoc\Dev\Workspace;

final class LocalEnvironment implements Environment
{
    public function __construct(
        private readonly Workspace $workspace,
        private readonly ProcessRunner $runner,
    ) {
    }

    public function canMapDirectoryNames(): bool
    {
        return false;
    }

    public function configure(string $lang, array $args): int
    {
        if (!$this->requireLocalPhp(80400, 'building the manual')) {
            return 1;
        }

        $args[] = '--with-php=' . PHP_BINARY;

        return $this->runner->run(
            array_merge([PHP_BINARY, $this->workspace->basedir() . '/configure.php'], $args),
            $this->workspace->rootdir()
        );
    }

    public function render(string $lang, string $docbook, string $format): int
    {
        $root = $this->workspace->rootdir();

        if (!$this->workspace->ensureRepo("$root/phd", 'https://github.com/php/phd.git')) {
            return 1;
        }

        return $this->runner->run([
            PHP_BINARY,
            "$root/phd/render.php",
            '--docbook',
            $this->workspace->basedir() . '/' . $docbook,
            '--output=' . $this->workspace->langDir($lang) . '/output',
            '--package',
            'PHP',
            '--format',
            $format,
        ], $root);
    }

    public function lint(string $lang, array $args): int
    {
        if (!$this->requireLocalPhp(80500, 'docbook-cs')) {
            return 1;
        }

        $csdir = $this->workspace->rootdir() . '/docbook-cs';

        if (!$this->workspace->ensureRepo($csdir, 'https://github.com/php/docbook-cs.git')) {
            return 1;
        }

        $langdir = $this->workspace->langDir($lang);

        if (file_exists("$csdir/vendor/autoload.php")) {
            return $this->runner->run(
                array_merge([PHP_BINARY, "$csdir/bin/docbook-cs"], $args),
                $langdir
            );
        }

        // No composer install needed: docbook-cs has no runtime
        // dependencies, so a plain PSR-4 autoloader on src/ is enough to
        // run it in-process.
        spl_autoload_register(static function (string $class) use ($csdir): void {
            if (str_starts_with($class, 'DocbookCS\\')) {
                require $csdir . '/src/' . str_replace('\\', '/', substr($class, 10)) . '.php';
            }
        });

        chdir($langdir);
        array_unshift($args, 'docbook-cs');

        return \DocbookCS\Application::withArguments($args)->run();
    }

    public function serve(string $lang, int $port, string $subdir): int
    {
        return $this->runner->run([
            PHP_BINARY,
            '-S',
            "localhost:$port",
            '-t',
            $this->workspace->langDir($lang) . '/output' . $subdir,
        ]);
    }

    public function serveWebDoc(int $port): int
    {
        $webdoc = $this->workspace->webDocDir();

        // router.php resolves www/ relative to the working directory, so
        // the server has to run from inside the web-doc checkout.
        return $this->runner->run(
            [PHP_BINARY, '-S', "localhost:$port", 'router.php'],
            $webdoc,
            [
                'PHPDOC_GIT_DIR' => $this->workspace->rootdir(),
                'SQLITE_DIR' => "$webdoc/sqlite",
                'BASE_DOCS_PATH' => $this->workspace->basedir() . '/docs',
            ]
        );
    }

    public function generateRevisionDb(array $langs): int
    {
        if (!$this->requireLocalPhp(80100, 'generating status.sqlite')) {
            return 1;
        }

        // genrevdb resolves en and each language relative to the working
        // directory, so it must run from the workspace root.
        return $this->runner->run(array_merge([
            PHP_BINARY,
            $this->workspace->basedir() . '/scripts/translation/genrevdb.php',
            $this->workspace->webDocDir() . '/sqlite/status.sqlite.new',
        ], $langs), $this->workspace->rootdir());
    }

    public function shell(string $lang): int
    {
        fwrite(STDERR, "error: docker shell requires Docker.\n");

        return 1;
    }

    public function buildImage(): int
    {
        fwrite(STDERR, "error: docker build requires Docker.\n");

        return 1;
    }

    private function requireLocalPhp(int $minimum, string $what): bool
    {
        if (PHP_VERSION_ID >= $minimum) {
            return true;
        }

        $need = sprintf('%d.%d', intdiv($minimum, 10000), intdiv($minimum % 10000, 100));
        fwrite(STDERR, "error: $what requires PHP $need+ without Docker (this is PHP "
            . PHP_VERSION . "). Install Docker or a newer PHP.\n");

        return false;
    }
}
