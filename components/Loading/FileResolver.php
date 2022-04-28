<?php

namespace PHPDoc\Internal\Loading;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FileResolver implements FileResolverInterface
{
    protected string $root;
    private string $prefix = '';
    private string $suffix = '';
    private array $excluded = [];
    private array $resolved = [];

    public function __construct(string $root)
    {
        $this->setRoot($root);
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Set root directory where files will be loaded
     */
    public function setRoot(string $root): void
    {
        $this->root = rtrim(trim($root), '/').'/';
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Prefix of loaded files
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = trim($prefix, '/');
    }

    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * Suffix of loaded files
     */
    public function setSuffix(string $suffix): void
    {
        $this->suffix = trim($suffix, '/');
    }

    public function getExcludes(): array
    {
        return $this->excluded;
    }

    /**
     * All the files under these directories will be ignored
     */
    public function excludes(array $dirs): void
    {
        foreach ($dirs as $dir) {
            $this->excluded[] = trim($dir, '/');
        }
    }

    /**
     * return loaded files
     */
    public function getResolved(): array
    {
        if (empty($this->resolved)) {
            $this->resolve();
        }

        return $this->resolved;
    }

    public function resolve(): void
    {
        $this->resolved = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root));
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir() || !$this->isValidFile($file)) {
                continue;
            }

            $this->resolved[] = $file->getRealPath();
        }
    }

    public function isValidFile(SplFileInfo $file): bool
    {
        $info = $file->getFileInfo();
        return file_exists($info->getRealPath()) && $this->isValidPath($info->getRealPath()) && $this->hasValidName($info->getFilename());
    }

    public function hasValidName(string $file): bool
    {
        return $this->hasValidPrefix($file) && $this->hasValidSuffix($file);
    }

    public function isValidPath(string $dir): bool
    {
        $dir = '/'.trim($dir, '/').'/';
        foreach ($this->excluded as $exclude) {
            // important to avoid relative access to invalid directories: '/in-valid/../in-not-valid'
            $dir = str_replace('/'.$exclude.'/..', '', $dir);

            if (str_contains($dir, '/'.$exclude.'/')) {
                return false;
            }
        }

        return true;
    }

    private function hasValidPrefix(string $file): bool
    {
        if (empty($this->prefix)) {
            return true;
        }

        return str_starts_with($file, $this->prefix);
    }

    private function hasValidSuffix(string $file): bool
    {
        if (empty($this->suffix)) {
            return true;
        }

        return str_ends_with($file, $this->suffix);
    }
}
