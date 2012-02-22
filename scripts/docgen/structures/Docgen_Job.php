<?php
define("DOCGEN_VERSION", "alpha");

abstract class Docgen_Job {
    protected $reflection;
    
    public $parameters = array(
        'pecl'=>false,
        'seealso'=>false,
        'example'=>false
    );
    
    public $documents = array();
    
    abstract public function execute();
    
    protected static function format_identifier($name) {
        return preg_replace(array('/[^[:alnum:]]/', '/^-+/'), array('-', ''), strtolower($name));
    }
    
    protected function addExamples() {
        
    }
    
    protected function addSeeAlso() {
        
    }
    
    protected function addLocalVariables(XMLWriter $writer) {
		$writer->writeComment(
' Keep this comment at the end of the file
24	Local variables:
25	mode: sgml
26	sgml-omittag:t
27	sgml-shorttag:t
28	sgml-minimize-attributes:nil
29	sgml-always-quote-attributes:t
30	sgml-indent-step:1
31	sgml-indent-data:t
32	indent-tabs-mode:nil
33	sgml-parent-document:nil
34	sgml-default-dtd-file:"~/.phpdoc/manual.ced"
35	sgml-exposed-tags:nil
36	sgml-local-catalogs:nil
37	sgml-local-ecat-files:nil
38	End:
39	vim600: syn=xml fen fdm=syntax fdl=2 si
40	vim: et tw=78 syn=sgml
41	vi: ts=1 sw=1 
'
		);
    }
}
