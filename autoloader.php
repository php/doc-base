<?php

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'PHPDoc\\Internal')) {
        $class = str_replace('PHPDoc\\Internal', '/components', $class);
    }

    $file = __DIR__.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';

    if (file_exists($file)) {
        return require $file;
    }
    return false;
});
