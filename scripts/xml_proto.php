<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 4                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2004 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.0 of the PHP license,       |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_0.txt.                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors:   Brad House <bradmssw@php.net>                             |
  +----------------------------------------------------------------------+
 
  $Id$
*/

/*
  REQUIRES: PHP 4.3.0-pre1 or higher

  This program generates XML documentation from the C source code
  of the extension.  For functions, the code _must_ have protos,
  for constants, the raw source code will be read and interpretted
  to generate the constants.xml file

  XML_PROTO v2.0 : Generate PHP documentation from proto defs (req PHP 4.3.0-pre1+)
  Usage: php xml_proto.php [opts] <extension name> <files to parse>
     Ex: php xml_proto.php mcve /php4/ext/mcve/*.c

  Options:
    -C : generate only constants.xml file (in current directory)
    -F : generate only fuction reference files (all will be placed in current directory)
    -A : (default) generate full documentation template
         This will generate <extension name>/ directory which reference.xml and contents.xml
         will be created in, and a subdirectory  <extension name>/functions/ which all
         function xml reference files will be generated in
*/

/* CHANGELOG:
   10/??/02 v1.0 - Original Release
   10/??/02 v1.1 - replace & with &amp;
   10/??/02 v1.2 - the Revision would be changed in static text meant for
                   insert into generated XML files (CVS did it)
   10/26/02 v2.0 - Additional scanning for constants, and generation of constants.xml
                 - Generation of references.xml template
                 - functions with optional arguments would not parse properly, now it does
                 - Wildcard scanning is now allowed
                 - Requires PHP 4.3.0-pre1 or higher now
                 - Usage is totally different
   05/06/04 v2.1 - Corrected filenames for OO functions
   01/03/05 v2.2 - Implemented the new doc style
   08/08/05 v2.3 - Refpurpose is on one line
*/

$version="2.3";

$funclist=array();
$num_funcs=0;

$constlist=array();
$num_const=0;

$generate_constants=1;
$generate_functions=1;
$source_files=array();
$extension_name="";
$constant_dir="";
$function_dir="";

function new_function()
{
  global $funclist, $num_funcs;
  $funclist[$num_funcs]["args"]=array();
  $funclist[$num_funcs]["num_args"]=0;
  $num_funcs++;
  return($num_funcs-1);
}

function fix_name($name)
{
  $replace = array('_' => '-',
                   '::' => '-',
                   '->' => '-');

  $name = strtr($name, $replace);
  $name = strtr($name, array('---' => '-'));

  return $name;
}

function function_add_name($num, $name)
{
  global $funclist, $num_funcs;

  $funclist[$num]["function_name"]=$name;
  $funclist[$num]["function_name_fix"]=fix_name($name);
  return(1);
}

function function_add_type($num, $type)
{
  global $funclist, $num_funcs;
  $funclist[$num]["function_type"]=$type;
  return(1);
}

function function_add_purpose($num, $purpose)
{
  global $funclist, $num_funcs;
  $funclist[$num]["purpose"]=$purpose;
  return(1);
}

function function_add_arg($num, $type, $argname, $isopt)
{
  global $funclist, $num_funcs;

  /* Avoid common mistake in parameter return types */
  if ($type == 'long') {
    $type = 'int';
  }
  $num_args=$funclist[$num]["num_args"];
  $funclist[$num]["args"][$num_args]["type"]=$type;
  $funclist[$num]["args"][$num_args]["variable"]=$argname;
  $funclist[$num]["args"][$num_args]["isopt"]=$isopt;
  $funclist[$num]["num_args"]++;

  return(1);
}

function write_cvsignore()
{
  $filename = "{$GLOBALS['constant_dir']}.cvsignore";
  if (!$fp = fopen($filename, 'w')) {
    echo "Failed writing: $filename\n";
    return(0);
  }
  fwrite($fp, "functions.xml\n");
  fclose($fp);
  echo "Wrote: $filename\n";
  return(1);
  
}

function write_reference_xml()
{
  global $extension_name, $constant_dir, $version;
  global $num_const;

  $filename= $constant_dir. "reference.xml";
  $fp=fopen($filename, "wb");
  if (!$fp) {
    echo "Failed writing: $filename\n";
    return(0);
  }

  fwrite($fp, '<?xml version="1.0" encoding="iso-8859-1"?>'."\n" .
       '<!-- $'.'Revision: 1.1 $ -->'."\n" .
       "<!-- Purpose:  -->\n" .
       "<!-- Membership:  -->\n" .
       "<!-- Generated by xml_proto.php v" . $version . ". Found in /scripts directory of phpdoc. -->\n" .
       "<reference id=\"ref." . $extension_name . "\">\n" .
       " <title>$extension_name Functions</title>\n" .
       " <titleabbrev>$extension_name</titleabbrev>\n" .
       "\n" .
       " <partintro>\n" .
       "  <section id=\"" . $extension_name . ".intro\">\n" .
       "   &reftitle.intro;\n" .
       "   <para>\n" .
       "    This is the " . $extension_name . " extension.  It\n" .
       "    currently only lists the proto definitions.\n" .
       "   </para>\n" .
       "  </section>\n" .
       "  <section id=\"" . $extension_name . ".requirements\">\n" .
       "   &reftitle.required;\n" .
       "   <para>\n" .
       "    To be written. For example what external libraries are required\n" .
       "   </para>\n" .
       "  </section>\n\n" .
       "  <!-- Information found in configure.xml -->\n" .
       "  &reference.".$extension_name.".configure;\n" .
       "  <!-- Information found in ini.xml -->\n" .
       "  &reference.".$extension_name.".ini;\n\n" .
       "  <section id=\"" . $extension_name . ".resources\">\n" .
       "   &reftitle.resources;\n" .
       "   <para>\n" .
       "    Type of resource types (link id, etc.) this extension returns.\n" .
       "    &no.resource;\n" .
       "   </para>\n" .
       "  </section>\n");
       if ($num_const > 0) {
         fwrite($fp, "  <!-- Information found in constants.xml -->\n" .
                     "  &reference." . $extension_name . ".constants;\n");
       }
       fwrite($fp, " </partintro>\n" .
       " &reference." . $extension_name . ".functions;\n" .
       "</reference>\n\n" .
       "<!-- Keep this comment at the end of the file\n" .
       "Local variables:\n" .
       "mode: sgml\n" .
       "sgml-omittag:t\n" .
       "sgml-shorttag:t\n" .
       "sgml-minimize-attributes:nil\n" .
       "sgml-always-quote-attributes:t\n" .
       "sgml-indent-step:1\n" .
       "sgml-indent-data:t\n" .
       "indent-tabs-mode:nil\n" .
       "sgml-parent-document:nil\n" .
       "sgml-default-dtd-file:\"../../../manual.ced\"\n" .
       "sgml-exposed-tags:nil\n" .
       "sgml-local-catalogs:nil\n" .
       "sgml-local-ecat-files:nil\n" .
       "End:\n" .
       "vim600: syn=xml fen fdm=syntax fdl=2 si\n" .
       "vim: et tw=78 syn=sgml\n" .
       "vi: ts=1 sw=1\n" .
       "-->\n");
  fclose($fp);
  
  echo "Wrote: $filename\n";
  return(1);
}

function write_functions_xml()
{
  global $funclist, $num_funcs;
  global $function_dir, $version;

  $filename="";
  $fp=0;

  for ($i=0; $i<$num_funcs; $i++) {
    $filename= $function_dir . $funclist[$i]["function_name_fix"] . ".xml";
    $fp=fopen($filename, "wb");
    if (!$fp) {
      echo "Failed writing: $filename\n";
      continue;
    }
    $fixname  = trim($funclist[$i]["function_name_fix"]);
    $funcname = trim($funclist[$i]["function_name"]);
    $purpose  = trim($funclist[$i]["purpose"]);
    $functype = trim($funclist[$i]["function_type"]);

    fwrite($fp, '<?xml version="1.0" encoding="iso-8859-1"?>'."\n" .
               '<!-- $'.'Revision: 1.1 $ -->'."\n" .
               "<!-- Generated by xml_proto.php v" . $version . ". Found in /scripts directory of phpdoc. -->\n" .
               "<refentry id=\"function." . $fixname . "\">\n" .
               " <refnamediv>\n" .
               "  <refname>$funcname</refname>\n" .
               "  <refpurpose>$purpose</refpurpose>\n" .
               " </refnamediv>\n" .
               " <refsect1 role=\"description\">\n" .
               "  &reftitle.description;\n" .
               "  <methodsynopsis>\n" .
               "   <type>$functype</type><methodname>$funcname</methodname>\n");

    $argnames = array();
    for ($j=0; $j<$funclist[$i]["num_args"]; $j++) {
      $argtype = $funclist[$i]["args"][$j]["type"];
      $argname = $funclist[$i]["args"][$j]["variable"];
      $isref = (strpos($argname, '&') === 0);
      if ($isref) {
        $argname = substr($argname, 1);
      }
      $isopt=$funclist[$i]["args"][$j]["isopt"];
      fwrite($fp, "   <methodparam" . ($isopt ? " choice=\"opt\"" : "") . "><type>$argtype</type><parameter" . ($isref ? " role=\"reference\"" : "") . ">$argname</parameter></methodparam>\n");
      $argnames[] = $argname;

    }
    if ($funclist[$i]["num_args"] == 0){
      fwrite($fp, "   <void/>\n");
    }

    fwrite($fp, "  </methodsynopsis>\n\n" .
               "  &warn.undocumented.func;\n\n" .
               " </refsect1>\n" .
               " <refsect1 role=\"parameters\">\n" .
               "  &reftitle.parameters;\n" .
               "  <para>\n"
    );
    if (count($argnames) > 0) {
        $tmp = "   <variablelist>\n";
        foreach ($argnames as $argname) {
            $tmp .= '' .
            "    <varlistentry>\n" .
            "     <term><parameter>{$argname}</parameter></term>\n" .
            "     <listitem>\n" .
            "      <para>\n" .
            "       Its description\n" .
            "      </para>\n" .
            "     </listitem>\n" .
            "    </varlistentry>\n";
        }
        $tmp .= "   </variablelist>\n";
        fwrite($fp, $tmp);
    }
    fwrite ($fp, "  </para>\n </refsect1>\n");
    fwrite($fp, 
        " <refsect1 role=\"returnvalues\">\n" .
        "  &reftitle.returnvalues;\n" .
        "  <para>\n" .
        "   What the function returns, first on success, then on failure. See\n" .
        "   also the &amp;return.success; entity\n" .
        "  </para>\n" .
        " </refsect1>\n"
    );
    fwrite($fp,
        "\n <!-- Use when EXCEPTIONS exist\n" .
        " <refsect1 role=\"exceptions\">\n" .
        "  &reftitle.exceptions;\n" .
        "  <para>\n" .
        "   When does this function throw exceptions?\n" .
        "  </para>\n" .
        " </refsect1>\n" .
        " -->\n\n"
    );
    fwrite($fp,
        "\n <!-- Use when a CHANGELOG exists\n" .
        " <refsect1 role=\"changelog\">\n" .
        "  &reftitle.changelog;\n" .
        "  <para>\n" .
        "   <informaltable>\n" .
        "    <tgroup cols=\"2\">\n" .
        "     <thead>\n" .
        "      <row>\n" .
        "       <entry>&Version;</entry>\n" .
        "       <entry>&Description;</entry>\n" .
        "      </row>\n" .
        "     </thead>\n" .
        "     <tbody>\n" .
        "      <row>\n" .
        "       <entry>Enter the PHP version of change here</entry>\n" .
        "       <entry>Description of change</entry>\n" .
        "      </row>\n" .
        "     </tbody>\n" .
        "    </tgroup>\n" .
        "   </informaltable>\n" .
        "  </para>\n" .
        " </refsect1>\n" .
        " -->\n\n"
    );
    fwrite($fp,
        "\n <!-- Use when examples exist\n" .
        " <refsect1 role=\"examples\">\n" .
        "  &reftitle.examples;\n" .
        "  <para>\n" .
        "   <example>\n" .
        "    <title>A <function>$funcname</function> example</title>\n" .
        "    <para>\n" .
        "     Any text that describes the purpose of the example, or\n" .
        "     what goes on in the example should go here (inside the\n" .
        "     <example> tag, not out\n" .
        "    </para>\n" .
        "    <programlisting role=\"php\">\n" .
        "<![CDATA[\n" .
        "<?php\n" .
        "if (\$anexample === true) {\n" .
        "    echo 'Use the PEAR Coding Standards';\n" .
        "}\n" .
        "?>\n" .
        "]]>\n" .
        "    </programlisting>\n" .
        "    &example.outputs;\n" .
        "    <screen>\n" .
        "<![CDATA[\n" .
        "Use the PEAR Coding Standards\n" .
        "]]>\n" .
        "    </screen>\n" .
        "   </example>\n" .
        "  </para>\n" .
        " </refsect1>\n" .
        " -->\n\n"
    );
    fwrite($fp,
        "\n <!-- Use when adding See Also links\n" .
        " <refsect1 role=\"seealso\">\n" .
        "  &reftitle.seealso;\n" .
        "  <para>\n" .
        "   <simplelist>\n" .
        "    <member><function></function></member>\n" .
        "    <member>Or <link linkend=\"somethingelse\">something else</link></member>\n" .
        "   </simplelist>\n" .
        "  </para>\n" .
        " </refsect1>\n" .
        " -->\n\n"
    );
    fwrite($fp,
        "\n</refentry>\n\n" .
        "<!-- Keep this comment at the end of the file\n" .
        "Local variables:\n" .
        "mode: sgml\n" .
        "sgml-omittag:t\n" .
        "sgml-shorttag:t\n" .
        "sgml-minimize-attributes:nil\n" .
        "sgml-always-quote-attributes:t\n" .
        "sgml-indent-step:1\n" .
        "sgml-indent-data:t\n" .
        "indent-tabs-mode:nil\n" .
        "sgml-parent-document:nil\n" .
        "sgml-default-dtd-file:\"../../../../manual.ced\"\n" .
        "sgml-exposed-tags:nil\n" .
        "sgml-local-catalogs:nil\n" .
        "sgml-local-ecat-files:nil\n" .
        "End:\n" .
        "vim600: syn=xml fen fdm=syntax fdl=2 si\n" .
        "vim: et tw=78 syn=sgml\n" .
        "vi: ts=1 sw=1\n" .
        "-->\n"
    );
    fclose($fp);
    echo "Wrote: $filename\n";
  }
  return(1);
}

function read_file($filename)
{
  $fp = fopen($filename, "rb");
  if ($fp == 0) return("");
  $buffer=fread($fp, filesize($filename));
  fclose($fp);
  return($buffer);
}

function parse_desc($func_num, $data)
{
  $len=0;
  $i=0;
  $c=0;
  $temp="";
  $temp_len=0;
  $spaces=0;

  $len=strlen($data);
  for ($i=0; $i<$len; $i++) {
    $c=substr($data, $i, 1);
    switch ($c) {
      case '\r':
      case '\n':
      case ' ':
        if (!$spaces) {
          $spaces=1;
          $temp .= ' ';
          $temp_len++;
        }
        break;

      default:
        if ($c != '\r' && $c != '\n') {
          $spaces=0;
          $temp .= $c;
          $temp_len++;
        }
        break;
    }
  }
  function_add_purpose($func_num, $temp);
  return(1);
}

function parse_proto($proto)
{
    $len=0;
    $i=0;
    $c=0;
    $done=0;
    $start=0;
    $func_number=-1;
    $got_proto_def=0;
    $got_proto_type=0;
    $got_proto_name=0;
    $got_arg_type=0;
    $start_args=0;
    $temp="";
    $temp2="";
    $temp_len=0;
    $isopt=0;

    $len=strlen($proto);

    for ($i=0; $i<$len; $i++) {
        $c=substr($proto, $i, 1);
        switch ($c) {
        case '\r':
        case '\n':
        case ' ':
            if ($temp_len) {
              if (!$got_proto_def) {
                if (strcasecmp($temp, "proto") != 0) {
                  // Possibly just a comment, don't output error info
                  // echo "Not a proper proto definition: $proto\n";
                  return(0);
                } else {
                    $got_proto_def=1;
                }
              } else if (!$got_proto_type) {
                  $func_number=new_function();
                  function_add_type($func_number, $temp);
                  $got_proto_type=1;
              } else if (!$got_proto_name) {
                  function_add_name($func_number, $temp);
                  $got_proto_name=1;
              } else if ($start_args && !$got_arg_type) {
                  $got_arg_type=1;
                  $temp2=$temp;
              } else if ($start_args && $got_arg_type) {
                  $got_arg_type=0;
                  function_add_arg($func_number, $temp2, $temp, $isopt);
                  $temp2="";
              }
              $temp_len=0;
              $temp="";
            }
            break;
      
        case '[':
            if ($got_proto_name) {
              $isopt=1;
            } else {
                echo "Not a proper proto definition -5: $proto\n";
            }
            break;
      
        case ']':
            if ($got_proto_name && $isopt) {
            } else {
                echo "Not a proper proto definition -6: $proto\n";
            }
            break;

        case '(':
            if ($got_proto_type && $got_proto_def &&!$got_proto_name) {
              function_add_name($func_number, $temp);
              $temp="";
              $temp_len=0;
              $start_args=1;
              $got_proto_name=1;
            } else {
                echo "Not a proper proto definition -2: $proto\n";
                return(0);
            }

            break;

        case ')':
            if ($start_args) {
              if ($got_arg_type && $temp_len) {
                function_add_arg($func_number, $temp2, $temp, $isopt);
                $temp="";
                $temp_len=0;
              }
              $done=1;
            } else {
                echo "Not a proper proto definition -4: $proto\n";
                return(0);
            }
            break;

        case ',':
            if ($start_args && $got_arg_type) {
              $got_arg_type=0;
              function_add_arg($func_number, $temp2, $temp, $isopt);
              $temp2="";
              $temp="";
              $temp_len=0;
            } else if ($temp && !$temp2) {
                echo "Not a proper proto definition -3: $temp2 : $temp : $proto\n";
                return(0);
            }
            break;

        default:
            if ($c != '\r' && $c != '\n') {
              $temp .= $c;
              $temp_len++;
            }
            break;
        }
        if ($done) {
          $start=$i+1;
          break;
        }
    }
    parse_desc($func_number, substr($proto, $start));
    return(1);
}

function parse_file($buffer)
{
  global $funclist, $num_funcs;

  $temp1="";
  $temp2="";
  $ptr="";
  $args="";

  $ptr=$buffer;
  while (1) {
    $temp1=strstr($ptr, "{{{");
    if ($temp1 == false) break;
    $temp2=strstr($temp1, "*/");
    if ($temp2 == false) break;
    $args=substr($temp1, 3, strlen($temp1)-strlen($temp2)-3);
    parse_proto($args);
    $ptr=$temp2;
  }
  return(1);
}

function add_constant_to_list($name, $type)
{
  global $constlist;
  global $num_const;
  
  $constlist[$num_const]["name"]=$name;
  $constlist[$num_const]["type"]=$type;

  $num_const++;
  return(1);
}

function add_constant($varlist, $type)
{
    $on_name=0;
    $len=strlen($varlist);
    for ($i=0; $i<$len; $i++) {
        $c=substr($varlist, $i, 1);
        switch($c) {
        case '"';
            if (!$on_name) {
              $on_name=1;
              $name="";
            } else {
                $on_name=0;
                add_constant_to_list($name, $type);
                return(1);
            }
            break;

        case ',':
            return(0);
            break;

        default:
            if ($on_name) {
              $name .= $c;
            }
            break;

        }
     }
     return(0);
}

function scan_for_constants_byref($buffer, $string, $type)
{
  $ptr=$buffer;

  while (1) {
    $temp=stristr($ptr, $string);
    if (!$temp) { return(1); }
    $temp2=substr($temp, strlen($string), strlen($temp)-strlen($string));
    $temp3=stristr($temp2, "(");
    if (!$temp3) { return(1); }
    $temp4=substr($temp3, 1, strlen($temp3)-1);
    $temp5=stristr($temp4, ")");
    if (!$temp5) { return(1); }
    $varlist=substr($temp4, 0, strlen($temp4)-strlen($temp5));
    if (!add_constant($varlist, $type)) {
      echo "Invalid constant definition: ";
      $str=substr($temp, 0, strlen($temp)-strlen($temp5)+1);
      echo $str;
      echo "\n";
    }
    $ptr=$temp5;
  }
  return(0);
}

function scan_for_constants($buffer)
{
  scan_for_constants_byref($buffer, "REGISTER_LONG_CONSTANT", "integer");
  scan_for_constants_byref($buffer, "REGISTER_DOUBLE_CONSTANT", "float");
  scan_for_constants_byref($buffer, "REGISTER_STRING_CONSTANT", "string");
}

function write_constants_xml()
{
  global $constant_dir;
  global $constlist;
  global $num_const;
  global $extension_name;
  global $version;

  if ($num_const == 0) {
    echo "No constants found, aborting write of constants.xml\n";
    return(1);
  }

  $filename = $constant_dir . "constants.xml";
  $fp=fopen($filename, "wb");
  if (!$fp) {
    echo "Failed writing: $filename\n";
    return(0);
  }

  fwrite($fp, "<?xml version='1.0' encoding='iso-8859-1'?>\n" .
              "<!-- $" . "Revision: 1.1 $ -->\n" .
               "<!-- Generated by xml_proto.php v" . $version . ". Found in /scripts directory of phpdoc. -->\n" .
               "<section id=\"" . $extension_name . ".constants\">\n" .
               " &reftitle.constants;\n" .
               " &extension.constants;\n" .
               " <variablelist>\n");
  for ($i=0; $i<$num_const; $i++) {
    $type=$constlist[$i]["type"];
    if (strcasecmp($type, "integer") == 0) {
      $linkend="<type>integer</type>";
    } else if (strcasecmp($type, "string") == 0) {
      $linkend="<type>string</type>";
    } else if (strcasecmp($type, "float") == 0) {
      $linkend="<type>float</type>";
    }

    fwrite($fp, "  <varlistentry>\n" .
                 "   <term>\n" .
                 "    <constant>" . $constlist[$i]["name"] . "</constant>\n" .
                 "     ($linkend)\n" .
                 "   </term>\n" .
                 "   <listitem>\n" .
                 "    <simpara>\n" .
                 "\n" .
                 "    </simpara>\n" .
                 "   </listitem>\n" .
                 "  </varlistentry>\n");
  }

  fwrite($fp,  " </variablelist>\n".
               "</section>\n\n".
               "<!-- Keep this comment at the end of the file\n" .
               "Local variables:\n" .
               "mode: sgml\n" .
               "sgml-omittag:t\n" .
               "sgml-shorttag:t\n" .
               "sgml-minimize-attributes:nil\n" .
               "sgml-always-quote-attributes:t\n" .
               "sgml-indent-step:1\n" .
               "sgml-indent-data:t\n" .
               "indent-tabs-mode:nil\n" .
               "sgml-parent-document:nil\n" .
               "sgml-default-dtd-file:\"../../../../manual.ced\"\n" .
               "sgml-exposed-tags:nil\n" .
               "sgml-local-catalogs:nil\n" .
               "sgml-local-ecat-files:nil\n" .
               "End:\n" .
               "vim600: syn=xml fen fdm=syntax fdl=2 si\n" .
               "vim: et tw=78 syn=sgml\n" .
               "vi: ts=1 sw=1\n" .
               "-->\n");
  fclose($fp);

  echo "Wrote: $filename\n";
  return(1);
}

function create_xml_docs()
{
  global $source_files, $generate_constants, $generate_functions;
  global $funclist, $num_funcs;
  $num=count($source_files);

  for ($i=0; $i<$num; $i++) {
    echo "READING " . $source_files[$i] . "\n";
    $contents=read_file($source_files[$i]);
    if ($contents == false || $contents == "") {
      echo "Could not read {$source_files[$i]}\n";
    }
    if ($generate_functions) {
      parse_file($contents);
    }
    if ($generate_constants) {
      echo "Scanning for constants\n";
      scan_for_constants($contents);
    }
  }


  if ($generate_functions) {
    echo "Writing function XML files\n";
    write_functions_xml();
    write_cvsignore();
  }

  if ($generate_constants) {
    echo "Writing constants XML file\n";
    write_constants_xml();
  }

  if ($generate_constants && $generate_functions) {
    echo "Writing reference XML file\n";
    write_reference_xml();
  }

  return(1);
}

function minimum_version($vercheck) {
  if(version_compare(phpversion(), $vercheck) == -1) {
    return false;
  } else {
    return true;
  }
}

function usage($progname)
{
  global $version;
  echo "XML_PROTO v$version : Generate PHP documentation from proto defs (req PHP 4.3.0-pre1+)\n";
  echo "Usage: " . $progname . " [opts] <extension name> <files to parse>\n";
  echo "   Ex: " . $progname . " mcve /php4/ext/mcve/*.c\n\n";
  echo "Options:\n";
  echo "  -C : generate only constants.xml file (in current directory)\n";
  echo "  -F : generate only fuction reference files (all will be placed in current directory)\n";
  echo "  -A : (default) generate full documentation template\n";
  echo "       This will generate <extension name>/ directory which reference.xml and contents.xml\n";
  echo "       will be created in, and a subdirectory  <extension name>/functions/ which all\n";
  echo "       function xml reference files will be generated in\n\n";
}

function parse_cli($progargc, $progargv)
{
  global $generate_constants, $generate_functions, $extension_name;
  global $constant_dir, $function_dir, $source_files;

  $unknown_arg=0;

  for ($i=1; $i<$progargc; $i++) {
    if (strcasecmp($progargv[$i], "-C") == 0) {
      $generate_constants=1;
      $generate_functions=0;
    } else if (strcasecmp($progargv[$i], "-F") == 0) {
      $generate_functions=1;
      $generate_constants=0;
    } else if (strcasecmp($progargv[$i], "-A") == 0) {
      $generate_functions=1;
      $generate_constants=1;
    } else {
      if ($unknown_arg == 0) {
        $extension_name=$progargv[$i];
        if ($generate_functions && $generate_constants) {
          $constant_dir="./$extension_name/";
          $function_dir="./$extension_name/functions/";
        }
      } else {
        $temp_source_files=glob($progargv[$i]);
        $num=count($source_files);
        $new_num=count($temp_source_files);
        for ($j=0; $j<$new_num; $j++) {
            $source_files[$num+$j]=$temp_source_files[$j];
        }
        $total=count($source_files);

        if ($total == 0) {
            die("FATAL ERROR: Could not find any PHP source files to parse\n");
        }
      }
      $unknown_arg++;
    }
  }

  return(1);
}

if (minimum_version('5.0')) {
$myargc=$argc;
$myargv=$argv;
} else {
$myargc=$_SERVER["argc"];
$myargv=$_SERVER["argv"];
}

if (!minimum_version("4.3.0")) {
  echo "You need PHP 4.3.0-pre1 or higher!\n";
  $ver=phpversion();
  echo "YOU HAVE: $ver\n";
  exit();
}

if ($myargc < 3) {
  usage($myargv[0]);
  exit();
}

if (!parse_cli($myargc, $myargv)) {
  usage($myargv[0]);
  exit();
}

/* Generating it all, create directory structure */
if ($generate_constants && $generate_functions) {
  if (is_dir("./" . $extension_name)) {
      echo "Warning: ./$extension_name already exists, continuing...\n";
  } else {
      mkdir("./" . $extension_name);
  }
  if (is_dir("./" . $extension_name . "/functions")) {
      echo "Warning: ./$extension_name/functions already exists, continuing...\n";
  } else {
      mkdir("./" . $extension_name . "/functions");
  }
}

/* Extension names may contain undesirable characters for element IDs */
$extension_name = fix_name($extension_name);

create_xml_docs();

echo <<<NOTES

Note: Also be sure to double check the documentation before commit as this
      script is still being tested.  Things to check:
      
      a) The parameter names in the prototype must be alphanumeric (no spaces 
         or other characters).  Sometimes this isn't the case in the PHP sources.
      b) Be sure optional parameters are listed as such, and vice versa.
      c) The script defaults to --with-{ext} but it could be different, like
         maybe --enable-{ext} OR a directory path is required or optional
      d) If you're writing over files in CVS, be 100% sure to check unified
         diffs before commit!
      e) Run script check-references.php and add role="reference" where required.
      f) Fill-in the Purpose and Membership comments in reference.xml and run
         extensions.xml.php.

Report problems to phpdoc@lists.php.net

NOTES;
?>
