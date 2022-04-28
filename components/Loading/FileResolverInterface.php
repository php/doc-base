<?php

namespace PHPDoc\Internal\Loading;

interface FileResolverInterface
{
    /**
     * Find the expected files
     */
    public function resolve(): void;

    /**
     * Return an array of resolved files
     */
    public function getResolved(): array;
}
