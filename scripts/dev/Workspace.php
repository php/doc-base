<?php

declare(strict_types=1);

namespace PhpDoc\Dev;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class Workspace
{
    public function __construct(
        private readonly string $basedir,
        private readonly ProcessRunner $runner,
        private readonly bool $assumeYes,
    ) {
    }

    public function basedir(): string
    {
        return $this->basedir;
    }

    public function rootdir(): string
    {
        return dirname($this->basedir);
    }

    public function isBaseLang(string $lang): bool
    {
        return in_array($lang, ['extensions', 'en'], true);
    }

    public function langDir(string $lang): string
    {
        $root = $this->rootdir();

        if (is_dir("$root/$lang")) {
            return realpath("$root/$lang");
        }

        return realpath("$root/doc-$lang") ?: "$root/$lang";
    }

    public function webDocDir(): string
    {
        $dir = $this->rootdir() . '/web-doc';

        return realpath($dir) ?: $dir;
    }

    public function ensureWebDocRepos(): bool
    {
        $dir = $this->webDocDir();

        return $this->ensureRepo($dir, 'https://github.com/php/web-doc.git')
            && $this->ensureRepo("$dir/shared", 'https://github.com/php/web-shared.git');
    }

    /**
     * Language checkouts present in the workspace, i.e. sibling directories
     * with a translation.xml (so en/doc-base are never included).
     *
     * @return array<string, string> Language code => absolute directory.
     */
    public function translationCheckouts(): array
    {
        $checkouts = [];

        foreach (glob($this->rootdir() . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (!is_file("$dir/translation.xml")) {
                continue;
            }

            $lang = basename($dir);
            $bare = !str_starts_with($lang, 'doc-');

            if (!$bare) {
                $lang = substr($lang, 4);
            }

            // When both "xx" and "doc-xx" exist the bare name wins,
            // matching langDir().
            if ($bare || !isset($checkouts[$lang])) {
                $checkouts[$lang] = realpath($dir);
            }
        }

        return $checkouts;
    }

    public function ensureRepo(string $dir, string $url): bool
    {
        if (is_dir($dir)) {
            return true;
        }

        if (!$this->confirm("Clone $url\n  into $dir?")) {
            fwrite(STDERR, "error: cannot continue without $dir.\n");
            return false;
        }

        return $this->runner->run(['git', 'clone', $url, $dir]) === 0;
    }

    /**
     * @param bool $mapNames Whether the environment can present a
     *                       doc-<lang> checkout under its language name
     *                       (Docker mounts can, local builds cannot).
     */
    public function ensureLang(string $lang, bool $mapNames): bool
    {
        $root = $this->rootdir();

        if (is_dir("$root/$lang")) {
            return true;
        }

        if (is_dir("$root/doc-$lang")) {
            if ($mapNames) {
                return true;
            }

            if (@symlink("doc-$lang", "$root/$lang")) {
                echo "Created symlink $lang -> doc-$lang\n";
                return true;
            }

            fwrite(STDERR, "error: found $root/doc-$lang but could not create a '$lang' "
                . "symlink next to it; rename the directory to '$lang' or use Docker.\n");
            return false;
        }

        $repo = 'doc-' . strtolower($lang);

        return $this->ensureRepo("$root/$lang", "https://github.com/php/$repo.git");
    }

    public function ensureLangRepos(string $lang, bool $mapNames): bool
    {
        if (!$this->isBaseLang($lang) && !$this->ensureLang('en', $mapNames)) {
            return false;
        }

        if ($lang === 'en') {
            return $this->ensureLang('en', $mapNames);
        }

        return $this->ensureLang($lang, $mapNames);
    }

    public function pullSideRepos(string $lang, bool $verbose = false): void
    {
        $root = $this->rootdir();
        $repos = [$this->basedir];

        if (!$this->isBaseLang($lang)) {
            $repos[] = $this->langDir('en');
        }

        foreach (['phd', 'docbook-cs', 'web-doc', 'web-doc/shared'] as $tool) {
            if (is_dir("$root/$tool")) {
                $repos[] = realpath("$root/$tool");
            }
        }

        foreach ($repos as $repo) {
            $this->pullRepo($repo, $verbose);
        }
    }

    private function pullRepo(string $dir, bool $verbose): void
    {
        if (!is_dir("$dir/.git")) {
            return;
        }

        $name = basename($dir);
        $branch = trim((string) $this->runner->output(['git', '-C', $dir, 'rev-parse', '--abbrev-ref', 'HEAD']));

        if (!in_array($branch, ['master', 'main'], true)) {
            if ($verbose) {
                echo "Not updating $name: on branch '$branch'.\n";
            }

            return;
        }

        $before = $this->runner->output(['git', '-C', $dir, 'rev-parse', 'HEAD']);

        if ($this->runner->run(['git', '-C', $dir, 'pull', '--ff-only', '--quiet', 'origin', $branch]) !== 0) {
            echo "Could not update $name; continuing with the current checkout.\n";
            return;
        }

        $after = $this->runner->output(['git', '-C', $dir, 'rev-parse', 'HEAD']);

        if ($before !== $after) {
            echo "Updated $name.\n";
        } elseif ($verbose) {
            echo "$name is up to date.\n";
        }
    }

    public function isShallowRepo(string $dir): bool
    {
        $out = $this->runner->output(['git', '-C', $dir, 'rev-parse', '--is-shallow-repository']);

        return trim((string) $out) === 'true';
    }

    public function removeTree(string $dir): void
    {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    public function getDocbookcsConfig(): ?string
    {
        $template = $this->basedir . '/docbookcs.dev.xml';
        $config = @file_get_contents($template);

        if ($config === false) {
            fwrite(STDERR, "error: cannot read $template.\n");
            return null;
        }

        return $config;
    }

    public function confirm(string $question): bool
    {
        if ($this->assumeYes) {
            return true;
        }

        if (!stream_isatty(STDIN)) {
            fwrite(STDERR, "error: confirmation needed but there is no terminal; re-run with --yes.\n");
            return false;
        }

        echo $question . ' [Y/n] ';
        $line = fgets(STDIN);

        // EOF (Ctrl-D, closed stdin) is not consent.
        if ($line === false) {
            echo "\n";
            return false;
        }

        $answer = strtolower(trim($line));
        return in_array($answer, ['', 'y', 'yes']);
    }
}
