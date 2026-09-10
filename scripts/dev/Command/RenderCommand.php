<?php

declare(strict_types=1);

namespace PhpDoc\Dev\Command;

use PhpDoc\Dev\Environment\Environment;
use PhpDoc\Dev\Options;
use PhpDoc\Dev\Workspace;

final class RenderCommand implements Command
{
    public function __construct(
        private readonly Workspace $workspace,
        private readonly Environment $environment,
        private readonly ConfigureCommand $configure,
    ) {
    }

    public function execute(Options $options): int
    {
        $ret = $this->configure->execute($options);

        if ($ret !== 0) {
            return $ret;
        }

        $lang = $options->lang;
        $format = $options->format;

        // PhD never cleans its output directory, so files from removed or
        // renamed pages would linger forever. Remove this format's own
        // output tree before rendering; other formats next to it are kept.
        $stale = $this->workspace->langDir($lang) . '/output/'
            . ($format === 'php' ? 'php-web' : 'php-chunked-xhtml');

        if (is_dir($stale)) {
            echo "Removing previous $stale\n";
            $this->workspace->removeTree($stale);
        }

        return $this->environment->render($lang, $this->docbook($options), $format);
    }

    /**
     * configure.php writes a partial build (--with-partial=xml-id) to
     * .manual.<xml-id>.xml, next to the always-written full .manual.xml.
     */
    private function docbook(Options $options): string
    {
        foreach ($options->args as $arg) {
            if (preg_match('/^--with-partial=(.+)$/', $arg, $m)) {
                return '.manual.' . $m[1] . '.xml';
            }
        }

        return '.manual.xml';
    }
}
