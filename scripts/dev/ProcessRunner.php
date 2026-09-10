<?php

declare(strict_types=1);

namespace PhpDoc\Dev;

final class ProcessRunner
{
    /**
     * @param list<string> $cmd
     * @param array<string, string> $env Extra environment variables.
     */
    public function run(array $cmd, ?string $cwd = null, array $env = []): int
    {
        $envp = $env === [] ? null : array_merge(getenv(), $env);
        $proc = @proc_open($cmd, [STDIN, STDOUT, STDERR], $pipes, $cwd, $envp);

        if (!is_resource($proc)) {
            fwrite(STDERR, "error: failed to execute {$cmd[0]}.\n");
            return 127;
        }

        return proc_close($proc);
    }

    /** @param list<string> $cmd */
    public function output(array $cmd, ?string $cwd = null): ?string
    {
        $spec = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $proc = @proc_open($cmd, $spec, $pipes, $cwd);

        if (!is_resource($proc)) {
            return null;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($proc) === 0 ? (string) $stdout : null;
    }

    /** @param list<string> $cmd */
    public function runQuiet(array $cmd): int
    {
        $spec = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $proc = @proc_open($cmd, $spec, $pipes);

        if (!is_resource($proc)) {
            return 127; /* command not found */
        }

        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($proc);
    }
}
