<?php
$srcpath = $argv[1];
if(!empty($argv[2])) $repourl = $argv[2]; else {
 preg_match("/URL\: (.*)/", `svn info {$srcpath}`, $repourl);
 $repourl = $repourl[1];
}

$result = array();

$extensions = get_loaded_extensions();
foreach($extensions as $extension) {
	$extension = strtolower($extension);
 $result[$extension] = array();
	if($extension == "core") {
  /*
		foreach(get_extension_funcs($extension) as $function) {
			$file = trim(`grep -Rl --include="*.c" "PHP_FUNCTION({$function})" {$srcpath}/main/`);
			if(!empty($file)) echo "{$function}: {$file}".PHP_EOL;
		}
  */
	} else {
		if(!file_exists("{$srcpath}/ext/{$extension}/")) continue;

		$extensionReflect = new ReflectionExtension($extension);
		$extensionName = $extensionReflect->name;

		$functions = $extensionReflect->getFunctions();
		foreach($functions as $function) {
			$file = trim(str_replace($srcpath, "", `grep -Rl --include="*.c" "PHP_FUNCTION({$function->name})" {$srcpath}/ext/{$extension}/`));
   if(!empty($file)) {
    $file = explode(PHP_EOL, $file);
    foreach($file as &$path) $path = $repourl.$path;
    $result[$extension][$function->name] = implode(", ", $file);
   }
		}

		$classes = $extensionReflect->getClasses();
		foreach($classes as $class) {
   $result[$extension][$class->name] = array();
   foreach($class->getMethods() as $method) {
    $file = trim(str_replace($srcpath, "", `grep -Rl --include="*.c" "PHP_METHOD({$extensionName}, {$method->name})" {$srcpath}/ext/{$extension}/`));
    if(!empty($file)) {
     $file = explode(PHP_EOL, $file);
     foreach($file as &$path) $path = $repourl.$path;
     $result[$extension][$class->name][$method->name] = implode(", ", $file);
    }
   }
   if(empty($result[$extension][$class->name])) unset($result[$extension][$class->name]);
		}
	}
 if(empty($result[$extension])) unset($result[$extension]);
}

ksort($result);
$entfile = fopen(__DIR__."/../entities/function-definitions.ent", "w");
fwrite($entfile, <<<HEAD
<!-- \$Revision$ -->

<!-- Repository links to where functions/methods are defined. -->

HEAD
);
foreach($result as $extension => $functions) {
 fwrite($entfile, <<<EXTNAME
<!-- {$extension} -->

EXTNAME
);
 foreach($functions as $function => $content) {
  if(is_string($content)) { // Function
   $funcname = str_replace("_", "-", strtolower($function));
   fwrite($entfile, <<<ENT
<!ENTITY reference.{$extension}.functions.{$funcname}.defpath '<!-- Defined in: {$content} -->'>

ENT
);
  } elseif(is_array($content)) foreach($content as $method => $url) { // Method
   $classname = strtolower($function);
   $methodname = str_replace("_", "-", strtolower($method));
   fwrite($entfile, <<<ENT
<!ENTITY reference.{$extension}.{$classname}.{$methodname}.defpath '<!-- Defined in: {$url} -->'>

ENT
);
  }
 }
}
