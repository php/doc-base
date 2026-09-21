<?php

declare(strict_types=1);

namespace PhpDoc\Dev\Environment;

use PhpDoc\Dev\ProcessRunner;
use PhpDoc\Dev\Workspace;

final class DockerEnvironment implements Environment
{
    private const IMAGE = 'php/doc-dev';

    public function __construct(
        private readonly Workspace $workspace,
        private readonly ProcessRunner $runner,
    ) {
    }

    public function canMapDirectoryNames(): bool
    {
        return true;
    }

    public function configure(string $lang, array $args): int
    {
        if (!$this->ensureImage()) {
            return 1;
        }

        return $this->dockerRun($this->mounts($lang), array_merge(['php', 'doc-base/configure.php'], $args));
    }

    public function render(string $lang, string $docbook, string $format): int
    {
        if (!$this->ensureImage()) {
            return 1;
        }

        return $this->dockerRun($this->mounts($lang), [
            'php',
            'phd/render.php',
            '--docbook',
            "doc-base/$docbook",
            '--output=/var/www/' . $lang . '/output',
            '--package',
            'PHP',
            '--format',
            $format,
        ]);
    }

    public function lint(string $lang, array $args): int
    {
        if (!$this->ensureImage()) {
            return 1;
        }

        return $this->dockerRun(
            $this->mounts($lang),
            array_merge(['php', '/var/www/docbook-cs/bin/docbook-cs'], $args),
            $this->gitSafeDirectoryEnv(),
            "/var/www/$lang"
        );
    }

    public function serve(string $lang, int $port, string $subdir): int
    {
        if (!$this->ensureImage()) {
            return 1;
        }

        // Inside the container the server must bind 0.0.0.0 to be reachable
        // through the published port; the host side stays localhost-only.
        return $this->dockerRun(
            $this->mounts($lang),
            ['php', '-S', "0.0.0.0:$port", '-t', "/var/www/$lang/output$subdir"],
            ['-p', "127.0.0.1:$port:$port"]
        );
    }

    public function serveWebDoc(int $port): int
    {
        if (!$this->ensureImage()) {
            return 1;
        }

        // The site shells out to git inside the mounted checkouts at
        // request time, hence the safe.directory override while serving.
        return $this->dockerRun(
            $this->webDocMounts(),
            ['php', '-S', "0.0.0.0:$port", 'router.php'],
            array_merge(
                [
                    '-p',
                    "127.0.0.1:$port:$port",
                    '-e',
                    'PHPDOC_GIT_DIR=/var/www',
                    '-e',
                    'SQLITE_DIR=/var/www/web-doc/sqlite',
                    '-e',
                    'BASE_DOCS_PATH=/var/www/doc-base/docs',
                ],
                $this->gitSafeDirectoryEnv()
            ),
            '/var/www/web-doc'
        );
    }

    public function generateRevisionDb(array $langs): int
    {
        if (!$this->ensureImage()) {
            return 1;
        }

        return $this->dockerRun(
            $this->webDocMounts(),
            array_merge(
                ['php', 'doc-base/scripts/translation/genrevdb.php', 'web-doc/sqlite/status.sqlite.new'],
                $langs
            ),
            $this->gitSafeDirectoryEnv()
        );
    }

    public function shell(string $lang): int
    {
        if (!$this->ensureImage()) {
            return 1;
        }

        return $this->dockerRun($this->mounts($lang), ['bash'], ['-it']);
    }

    public function buildImage(): int
    {
        return $this->build() ? 0 : 1;
    }

    private function ensureImage(): bool
    {
        $dockerfile = $this->workspace->basedir() . '/.docker/Dockerfile';
        $stamp = $this->workspace->basedir() . '/.docker/built';

        if (
            $this->runner->runQuiet(['docker', 'image', 'inspect', self::IMAGE]) === 0
            && file_exists($stamp)
            && filemtime($stamp) >= filemtime($dockerfile)
        ) {
            return true;
        }

        return $this->build();
    }

    private function build(): bool
    {
        $cmd = ['docker', 'build'];
        $ids = $this->unixIds();

        if ($ids !== null) {
            array_push($cmd, '--build-arg', 'UID=' . $ids[0]);
            array_push($cmd, '--build-arg', 'GID=' . $ids[1]);
        }

        array_push($cmd, '-t', self::IMAGE, $this->workspace->basedir() . '/.docker');

        if ($this->runner->run($cmd) !== 0) {
            return false;
        }

        touch($this->workspace->basedir() . '/.docker/built');

        return true;
    }

    private function mounts(string $lang): array
    {
        $root = $this->workspace->rootdir();
        $mounts = [realpath($this->workspace->basedir()) => '/var/www/doc-base'];

        $mounts[$this->workspace->langDir($lang)] = "/var/www/$lang";

        if (!$this->workspace->isBaseLang($lang)) {
            $mounts[$this->workspace->langDir('en')] = '/var/www/en';
        }

        foreach (['phd', 'docbook-cs'] as $tool) {
            if (is_dir("$root/$tool")) {
                $mounts[realpath("$root/$tool")] = "/var/www/$tool";
            }
        }

        return $mounts;
    }

    private function webDocMounts(): array
    {
        $mounts = [
            realpath($this->workspace->basedir()) => '/var/www/doc-base',
            $this->workspace->webDocDir() => '/var/www/web-doc',
            $this->workspace->langDir('en') => '/var/www/en',
        ];

        // Mount every translation checkout so PHPDOC_GIT_DIR=/var/www looks
        // like a full doc.php.net workspace to the site.
        foreach ($this->workspace->translationCheckouts() as $lang => $dir) {
            $mounts[$dir] = "/var/www/$lang";
        }

        return $mounts;
    }

    /** @return list<string> */
    private function gitSafeDirectoryEnv(): array
    {
        return [
            '-e',
            'GIT_CONFIG_COUNT=1',
            '-e',
            'GIT_CONFIG_KEY_0=safe.directory',
            '-e',
            'GIT_CONFIG_VALUE_0=*',
        ];
    }

    /**
     * @param array<string, string> $mounts Host path => container path.
     * @param list<string> $inner Command to run inside the container.
     * @param list<string> $extra Extra docker run arguments.
     */
    private function dockerRun(array $mounts, array $inner, array $extra = [], string $workdir = '/var/www'): int
    {
        // --init: without it the command runs as PID 1, which ignores
        // SIGINT, so Ctrl-C would leave the container running forever.
        $cmd = ['docker', 'run', '--rm', '--init'];

        foreach ($mounts as $host => $container) {
            array_push($cmd, '-v', "$host:$container");
        }

        array_push($cmd, '-w', $workdir);
        $ids = $this->unixIds();

        if ($ids !== null) {
            array_push($cmd, '-u', $ids[0] . ':' . $ids[1]);
        }

        $cmd = array_merge($cmd, $extra);
        $cmd[] = self::IMAGE;

        return $this->runner->run(array_merge($cmd, $inner));
    }

    /** @return array{int, int}|null */
    private function unixIds(): ?array
    {
        if (function_exists('posix_getuid')) {
            return [posix_getuid(), posix_getgid()];
        }

        return null;
    }
}
