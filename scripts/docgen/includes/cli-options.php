<?php
class Docgen_Options {
    protected static $option_spec = array(
        'short' => array(
            "e:", // Extension
            "c:", // Class
            "m:", // Method
            "f:", // Function
            
            "p", // Is a PECL extension
            "s", // Add see-also sections
            "x", // Add example sections
            
            "i:", // Include File
            "d:", // Path to phpdoc
            "o:", // Output directory
            "a", // Copy output to phpdoc
            "t", // Test mode
            
            "h", // Display help message
            "v", // Version information
            "V::", // Verbosity
            "q" // Quiet
        ),
        'long' => array(
            "extension:",
            "class:",
            "method:",
            "function:",
            
            "pecl",
            "seealso",
            "example",
            
            "include:",
            "phpdoc:",
            "output:",
            "copy",
            "test",
            
            "help",
            "version",
            "verbose::",
            "quiet"
        ),
        'multiples' => array(
            "e",
            "c",
            "m",
            "f",
            "i"
        )
    );
    
    public static $options = array(
        "extension" => null,
        "class" => null,
        "method" => null,
        "function" => null,
        "pecl" => false,
        "seealso" => false,
        "example" => false,
        "include" => null,
        "phpdoc" => null,
        "output" => "./output/",
        "copy" => false,
        "test" => false,
        "verbose" => 2
    );
    
    public static function process_options() {
        $options = getopt(implode("", self::$option_spec['short']), self::$option_spec['long']);
        
        // Conditions upon which to display the usage message and exit
        if ($options === FALSE // Invalid option usage
            || (array_key_exists("h", $options)
            || array_key_exists("help", $options)) // Help is requested
        ) {
            self::display_usage(true);
        }
        
        // Cannot be both verbose and quiet
        if ((!empty($options["q"]
            || !empty($options["quiet"]))
            && (!empty($options["v"])
                || !empty($options["verbose"]))
        ) {
            trigger_error("Option -v, --verbose and option -q, --quiet cannot be specified together.", E_USER_ERROR);
        }
        
        // Cannot copy when in test mode
        if ((!empty($options["a"])
            || !empty($options["copy"]))
            && (!empty($options["t"])
            || !empty($options["test"]))
        ) {
            trigger_error("Option -a, --copy and option -t, --test cannot be specified together.", E_USER_ERROR);
        }
        
        foreach (self::$option_spec['short'] as $index => $option) {
            $shortoption = trim($option, ":");
            $longoption = trim(self::$option_spec['long'][$index], ":");
            
            // Verify that options specified multiple times support it
            if ((is_array($options[$shortoption])
                || is_array($options[$longoption]))
                || (!empty($options[$shortoption])
                    && !empty($options[$longoption])
                && !in_array($shortoption, self::$option_spec['multiples'], true)
            ) {
                trigger_error("Option -{$shortoption}, --{$longoption} does not support multiple specifications.", E_USER_ERROR);
            }
            
            // Merge options specified multiple times into single options, or just use the one that's set
            if (is_array($options[$shortoption]) && is_array($options[$longoption])) {
                self::$options[$longoption] = array_merge($options[$shortoption], $options[$longoption]);
            } elseif (is_array($options[$shortoption]) && isset($options[$longoption])) {
                $options[$shortoption][] = $option[$longoption];
                self::$options[$longoption] = $options[$shortoption];
            } elseif (isset($options[$shortoption]) && is_array($options[$longoption])) {
                $options[$longoption][] = $option[$shortoption];
                self::$options[$longoption] = $options[$longoption];
            } elseif (isset($options[$shortoption) && isset($options[$longoption])) {
                self::$options[$longoption] = array($options[$shortoption], $options[$longoption]);
            } elseif (isset($options[$shortoption]) || isset($options[$longoption])) { // Just one version of the option is specified
                self::$options[$longoption] = isset($options[$shortoption])?$options[$shortoption]:$options[$longoption];
            }
        }
        
        self::validate_options();
    }
    
    public static function validate_options() {
        $options =& self::$options;
        
        // Validate job types
        foreach(array("extension", "class", "method", "function") as $jobtype) {
            if (!is_null($options[$jobtype])) {
                if (is_array($options[$jobtype])) {
                    foreach ($options[$jobtype] as $index => $item) {
                        if (($jobtype == "extension"
                            && !extension_loaded($item))
                            || ($jobtype == "class"
                            && !class_exists($item))
                            || ($jobtype == "method"
                            && $tmp = explode("::", $item)
                            && count($tmp) == 2
                            && method_exists($tmp[0], $tmp[1]))
                            || ($jobtype == "function"
                            && function_exists($item))
                        ) {
                            trigger_error("The '{$item}' {$jobtype} is not loaded. Documentation will not occur for this {$jobtype}.", E_USER_WARNING);
                            unset($option[$jobtype][$index];
                        }
                    }
                    if (empty($options[$jobtype])) $options[$jobtype] = null;
                } else {
                    if (($jobtype == "extension"
                        && !extension_loaded($options[$jobtype])
                        || ($jobtype == "class"
                        && !class_exists($options[$jobtype]))
                        || ($jobtype == "method"
                        && $tmp = explode("::", $options[$jobtype])
                        && count($tmp) == 2
                        && method_exists($tmp[0], $tmp[1]))
                        || ($jobtype == "function"
                        && function_exists($options[$jobtype]))
                    ) {
                        trigger_error("The '{$options[$jobtype]}' {$jobtype} is not loaded. Documentation will not occur for this {$jobtype}.", E_USER_WARNING);
                        $option[$jobtype] = null;
                    }
                }
            }
        }
        
        // Verify includes exist and can be read
        if (!is_null($options["include"])) {
            if (is_array($options["include"])) {
                foreach ($options["include"] as $index => $include) {
                    if(!is_readable($include)) {
                        trigger_error("The include '{$include}' does not exist, or is not readable. It will not be included.", E_USER_WARNING);
                        unset($options["include"][$index]);
                    }
                }
            } else {
                if(!is_readable($options["include"])) {
                    trigger_error("The file '{$options["include"]}' does not exist, or is not readable. It will not be included.", E_USER_WARNING);
                    $options["include"] = null;
                }
            }
        }
        
        // Verify the specified phpdoc location exists and can be read
        if (!is_null($options["phpdoc"]) && !is_readable($options["phpdoc"])) {
            trigger_error("The phpdoc path '{$options["phpdoc"]}' does not exist or is not readable. It will not be analyzed or copied to.", E_USER_WARNING);
            $options["phpdoc"] = null
            $options["copy"] = false;
        }
        
        // Verify that if copy is specified, the phpdoc location is specified and can be written to
        if ($options["copy"] === true && is_null($options["phpdoc"])) {
            trigger_error("The phpdoc location has not been specified. Files will not be copied there.");
            $options["copy"] = false;
        } elseif ($options["copy"] === true && !is_null($options["phpdoc"]) && !is_writeable($options["phpdoc"])) {
            trigger_error("The specified phpdoc location is not writeable. Files will not be copied there.");
            $options["copy"] = false;
        }
        
        // Verify that the output location is specified, and exists, or can be created, *or* test mode is enabled.
        if($options["test"] === false) {
            if ((!is_string($options["output"]) || empty($options["output"]))) {
                trigger_error("The output directory is empty, and test mode is not enabled.", E_USER_ERROR);
            } elseif (is_string($options["output"]) && !empty($options["output"]) && (!file_exists($options["output"]) && !is_writeable(dirname($options["output"])))) {
                trigger_error("The output directory does not exist, and cannot be created, and test mode is not enabled.", E_USER_ERROR);
            } elseif (is_string($options["output"]) && !empty($options["output"]) && file_exists($options["output"]) && !is_writeable($options["output"])) {
                trigger_error("The output directory exists, but is not writeable, and test mode is not enabled.", E_USER_ERROR);
            }
        }
    }
    
    public static function display_usage($exit = false) {
?>
Usage:  php docgen.php [OPTION]...
        ./docgen.php [OPTION]...

    -e, --extension     Generate documentation skeletons for the
                        specified extension(s). May be specified
                        multiple times.
    -c, --class         Generate documentation skeletons for the
                        specified class(es). May be specified multiple
                        times.
    -m, --method        Generate documentation skeletons for the
                        specified method(s). May be specified multiple
                        times.
    -f, --function      Generate documentation skeletons for the
                        specified function(s). May be specified multiple
                        times.

    -p, --pecl          Indicates that the generated documentation
                        skeletons are for a PECL extension.
    -s, --seealso       Includes "See also" sections in the generated
                        documentation skeletons.
    -x, --example       Includes "Example" sections in the generated
                        documentation skeletons.

    -i, --include       Includes the specified file(s) before the
                        documentation generation begins. May be
                        specified multiple times.
    -d, --phpdoc        Specifies the location of the manual sources,
                        for analysis and automatic copying of files.
    -o, --output        Specifies the directory to which the generated
                        documentation skeletons will be output. Defaults
                        to "./output/".
    -a, --copy          Copies the generated documentation skeletons
                        to the location of the manual sources. Requires
                        -d or --phpdoc be specified.
    -t, --test          Enables test mode. No files will be written or
                        copied.

    -h, --help          Show this message.
    -v, --version       Display version information.
    -V, --verbose       Display more output. Optionally specify 0-5 for
                        lowest to highest verbosity, respectively.
                        Defaults to 2.
    -q, --quiet         Suppress most output; alias for 0 verbosity.

Examples:
    # Generate documentation skeletons for the "reflection" extension
    php docgen.php -e reflection

    # Generate documentation skeletons for the ReflectionExtension class
    php docgen.php -c ReflectionExtension

    # Generate documentation skeletons for the getName method of the
    # ReflectionExtension class
    php docgen.php -m ReflectionExtension::getName

    # Generate documentation skeletons for the substr function
    php docgen.php -f substr
<?php
        if ($exit === true) exit;
    }
}
