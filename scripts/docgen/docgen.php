#!/usr/bin/env php
<?php
if(!extension_loaded("reflection")) trigger_error("docgen.php requires the Reflection extension", E_USER_ERROR);
if(!extension_loaded("dom")) trigger_error("docgen.php requires the DOM extension", E_USER_ERROR);

// 
require(__DIR__."/includes/cli-options.php");
