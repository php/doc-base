<?php
class Docgen_MethodJob extends Docgen_Job {
    public function __construct($class, $method, $parameters = null) {
        $this->reflection = new ReflectionMethod($class, $method);
        if(is_array($parameters)) $this->parameters = $parameters);
    }
    
    public function execute() {
        
    }
}
