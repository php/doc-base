#!/usr/bin/env php
<?php
// Required extensions
if(!extension_loaded("reflection")) trigger_error("Docgen requires the Reflection extension", E_USER_ERROR);
if(!extension_loaded("pcre")) trigger_error("Docgen requires the PCRE extension", E_USER_ERROR);
if(!extension_loaded("xmlwriter")) trigger_error("Docgen requires the DOM extension", E_USER_ERROR);

// Required includes
require_once(__DIR__."/structures/Docgen_JobManager.php");
require_once(__DIR__."/structures/Docgen_Job.php");
require_once(__DIR__."/includes/cli-options.php");

// Handle script options
Docgen_Options::process_options();

$parameters = array('pecl'=>Docgen_Options::$options["pecl"], 'seealso'=>Docgen_Options::$options["seealso"], 'example'=>Docgen_Options::$options["example"]);

if (is_array(Docgen_Options::$options["extension"])) {
    foreach (Docgen_Options::$options["extension"] as $extension) {
        Docgen_JobManager::queueJob(new Docgen_ExtensionJob($extension, $parameters));
    }
} elseif (!is_null(Docgen_Options::$options["extension"])) {
    Docgen_JobManager::queueJob(new Docgen_ExtensionJob(Docgen_Options::$options["extension"], $parameters));
}

if (is_array(Docgen_Options::$options["class"])) {
    foreach (Docgen_Options::$options["class"] as $class) {
        Docgen_JobManager::queueJob(new Docgen_ClassJob($class, $parameters));
    }
} elseif (!is_null(Docgen_Options::$options["class"])) {
    Docgen_JobManager::queueJob(new Docgen_ClassJob(Docgen_Options::$options["class"], $parameters));
}

if (is_array(Docgen_Options::$options["method"])) {
    foreach (Docgen_Options::$options["method"] as $method) {
        $method = explode("::", $method);
        DocGen_JobManager::queueJob(new Docgen_MethodJob($class, $method, $parameters));
    }
} elseif (!is_null(Docgen_Options::$options["method"])) {
    Docgen_JobManager::queueJob(new Docgen_MethodJob(Docgen_Options::$options["method"], $parameters));
}

if (is_array(Docgen_Options::$options["function"])) {
    foreach (Docgen_Options::$options["function"] as $function) {
        DocGen_JobManager::queueJob(new Docgen_FunctionJob($function, $parameters));
    }
} elseif (!is_null(Docgen_Options::$options["function"])) {
    Docgen_JobManager::queueJob(new Docgen_FunctionJob(Docgen_Options::$options["function"], $parameters));
}
