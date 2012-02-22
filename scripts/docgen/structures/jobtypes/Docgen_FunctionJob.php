<?php
class Docgen_FunctionJob extends Docgen_Job {
    public function __construct($name, $parameters = null) {
        $this->reflection = new ReflectionFunction($name);
        if(is_array($parameters)) $this->parameters = $parameters);
    }
    
    public function execute() {
        
    }
}
