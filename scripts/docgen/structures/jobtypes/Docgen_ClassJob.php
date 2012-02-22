<?php
class Docgen_ClassJob extends Docgen_Job {
    public function __construct($name, $parameters = null) {
        $this->reflection = new ReflectionClass($name);
        if(is_array($parameters)) $this->parameters = $parameters);
    }
    
    public function execute() {
        
    }
}
