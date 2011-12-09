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

foreach($result as $extension_name => $extension) {
	if(!file_exists("../../en/reference/{$extension_name}/")) continue;
	foreach($extension as $item_name => $item) {
		$item_name = strtolower($item_name);
		if(is_string($item)) { // Function
			if(!file_exists("../../en/reference/{$extension_name}/functions/") || !file_exists("../../en/reference/{$extension_name}/functions/{$item_name}.xml")) continue;
			echo `svn propset srcurl '{$item}' ../../en/reference/{$extension_name}/functions/{$item_name}.xml`;
		} elseif(is_array($item)) { // Method
			if(!file_exists("../../en/reference/{$extension_name}/{$item_name}/")) continue;
			foreach($item as $method_name => $url) {
				$method_name = strtolower($method_name);
				if(!file_exists("../../en/reference/{$extension_name}/{$item_name}/{$method_name}.xml")) continue;
				echo `svn propset srcurl '{$url}' ../../en/reference/{$extension_name}/{$item_name}/{$method_name}.xml`;
			}
		}
	}
}
