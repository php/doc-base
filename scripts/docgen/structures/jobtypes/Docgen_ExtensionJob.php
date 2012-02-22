<?php
class Docgen_ExtensionJob extends Docgen_Job {
    public function __construct($name, $parameters = null) {
        $this->reflection = new ReflectionExtension($name);
        if(is_array($parameters)) $this->parameters = $parameters;
    }
    
    public function execute() {
        $this->createBook();
    }
    
    public function createBook() {
        $extensionID = self::format_identifier($this->reflection->name);
        
        $bookDocument = new DOMDocument("1.0", "utf-8");
        $bookDocument->appendChild($bookDocument->createComment('$Revision$'));
        
        $book = $bookDocument->createElement("book");
        $book->setAttribute("xml:id", "book.{$extensionID}");
        $book->setAttribute("xmlns", "http://docbook.org/ns/docbook");
        $book->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
        $bookDocument->appendChild($book);
        
        $book->appendChild($bookDocument->createElement("title", $this->reflection->name));
        $book->appendChild($bookDocument->createElement("titleabbrev", $this->reflection->name."..."));
        
        $book->appendChild($bookDocument->createComment(" {{{ preface "));
            $preface = $bookDocument->createElement("preface");
            $preface->setAttribute("xml:id", "intro.{$extensionID}");
            $book->appendChild($preface);
            
            $preface->appendChild($bookDocument->createEntityReference("reftitle.intro"));
            $preface->appendChild($bookDocument->createElement("para", "Extension introduction."));
        $book->appendChild($bookDocument->createComment(" }}} "));
        
        $book->appendChild($bookDocument->createEntityReference("reference.{$extensionID}.setup"));
        $book->appendChild($bookDocument->createEntityReference("reference.{$extensionID}.constants"));
        $book->appendChild($bookDocument->createEntityReference("reference.{$extensionID}.reference"));
        
        $this->addLocalVariables($bookDocument, $bookDocument);
        
        $this->documents["reference/{$extensionID}/book.xml"] = $bookDocument;
    }
}
