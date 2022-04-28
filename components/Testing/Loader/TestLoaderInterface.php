<?php

namespace PHPDoc\Internal\Testing\Loader;

interface TestLoaderInterface
{
    public function load(): void;

    /**
     * @return array of real path file
     */
    public function getResources(): array;
}
