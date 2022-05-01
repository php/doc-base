<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 8                                                      |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2011 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.0 of the PHP license,       |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_0.txt.                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors:    Eugène d'Augier <eugene-augier@gmail.com>                |
  +----------------------------------------------------------------------+

  $Id$
*/
namespace PHPDoc\Internal\Testing\Loader;

use PHPDoc\Internal\Loading\FileResolver;
use PHPDoc\Internal\Loading\FileResolverInterface;
use SplFileInfo;

class TestClassLoader implements TestLoaderInterface
{
    private FileResolverInterface $resolver;
    private string $testDirName;
    private array $accepted = [];
    private array $resources = [];

    public function __construct(string $root, string $testDirName, string $testClassSuffix)
    {
        $this->resolver = new FileResolver($root);
        $this->setTestDirName($testDirName);
        $this->setTestClassSuffix($testClassSuffix);
    }

    /**
     * Get directory name where files can be loaded
     */
    public function getTestDirName(): string
    {
        return $this->testDirName;
    }

    /**
     * Set directory name where files can be loaded
     */
    public function setTestDirName(string $dir): void
    {
        $this->testDirName = '/'.trim($dir, '/').'/';
    }

    public function excludes(array $excluded)
    {
        $this->resolver->excludes($excluded);
    }

    /**
     * Will load files only in specified directories under the root dir
     */
    public function onlyIn(array $accepted): void
    {
        foreach ($accepted as $dir) {
            $this->accepted[] = $this->resolver->getRoot().trim($dir, '/').'/';
        }
    }

    /**
     * Will load only one file (ignore $root)
     */
    public function loadFile(string $file): void
    {
        $this->accepted = [$file];
    }

    /**
     * return loaded files
     */
    public function getResources(): array
    {
        if (empty($this->resources)) {
            $this->load();
        }

        return $this->resources;
    }
    
    public function load(): void
    {
        $this->resources = [];
        if (empty($this->accepted)) {
            $this->accepted[] = $this->resolver->getRoot();
        }

        foreach ($this->accepted as $accepted) {
            if ($this->resolver->isValidFile(new SplFileInfo($accepted))) {
                $this->addResource($accepted);
                continue;
            }

            $this->resolver->setRoot($accepted);
            $this->resolver->resolve();
            foreach ($this->resolver->getResolved() as $file) {
                if (!$this->isValidResource($file)) {
                    continue;
                }

                $this->addResource($file);
            }
        }
    }

    public function isValidResource(string $file): bool
    {
        $file = str_replace($this->testDirName.'..', '', $file);
        if (!$this->resolver->isValidFile(new SplFileInfo($file))) {
            return false;
        }

        return str_contains($file, $this->testDirName);
    }

    private function setTestClassSuffix(string $testClassSuffix): void
    {
        $testClassSuffix = trim($testClassSuffix, '/');
        if (!str_ends_with($testClassSuffix, '.php')) {
            $testClassSuffix .= '.php';
        }

        $this->resolver->setSuffix($testClassSuffix);
    }

    private function addResource(string $realpath): void
    {
        // get class name and remove .php ext
        $class = substr(basename($realpath), 0, -4);

        $this->resources[] = [$realpath, $class];
    }
}
