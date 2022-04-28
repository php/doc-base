<?php

namespace PHPDoc\Internal\Testing;

interface AssertInterface
{
    public function getFailures(): array;

    /**
     * Useful to get failures only for a specific testMethod
     */
    public function getLastFailures(): array;
}
