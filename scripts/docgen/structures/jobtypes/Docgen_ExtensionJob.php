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
        
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString("  ");
        $writer->startDocument("1.0", "UTF-8");
        $writer->writeComment(' $Revision$ ');
        
        $writer->startElement("book");
        $writer->writeAttribute("xml:id", "book.extname");
        $writer->writeAttribute("xmlns", "http://docbook.org/ns/docbook");
        $writer->writeAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
        
			$writer->writeElement("title", $this->reflection->name);
			$writer->writeElement("titleabbrev", $this->reflection->name."...");
			
			$writer->writeRaw("\n");
			
			$writer->writeComment(" {{{ preface ");
			$writer->startElement("preface");
				$writer->writeRaw("\n    &reftitle.intro;\n");
				$writer->writeElement("para", "Extension introduction.");
			$writer->endElement();
			$writer->writeComment(" }}} ");
			
			$writer->writeRaw("\n");
			
			$writer->writeRaw("  &reference.{$extensionID}.setup;\n");
			$writer->writeRaw("  &reference.{$extensionID}.constants;\n");
			$writer->writeRaw("  &reference.{$extensionID}.reference;\n");
			
			$writer->writeRaw("\n");
		
		$writer->endElement();
		$writer->endDocument();
		
		$this->addLocalVariables($writer);
        
        $this->documents["reference/{$extensionID}/book.xml"] = $writer->flush();
    }
}
