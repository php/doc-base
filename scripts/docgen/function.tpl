<?xml version="1.0" encoding="iso-8859-1"?>
<!-- $Revision: 1.2 $ -->

<refentry xml:id="function.{FUNCTION_NAME_ID}" xmlns="http://docbook.org/ns/docbook">
 <refnamediv>
  <refname>{FUNCTION_NAME}</refname>
  <refpurpose>Description</refpurpose>
 </refnamediv>

 <refsect1 role="description">
  &reftitle.description;
  <methodsynopsis>
   <type>void</type><methodname>{FUNCTION_NAME}</methodname>
   {PARAMETERS}
  </methodsynopsis>
  <para>
   The function description goes here.
  </para>
 </refsect1>

 <refsect1 role="parameters">
  &reftitle.parameters;
  {PARAMETERS_DESCRIPTION}
 </refsect1>

 <refsect1 role="returnvalues">
  &reftitle.returnvalues;
  <!-- See also &return.success; -->
  <para>
   What is returned on success and failure
  </para>
 </refsect1>

 <refsect1 role="examples">
  &reftitle.examples;
  <para>
   <example>
    <title><function>{FUNCTION_NAME}</function> example</title>
    <para>
      Any text that describes the purpose of the example, or what
      goes on in the example should be here.
    </para>
    <programlisting role="php">
<![CDATA[
<?php

/* ... */

?>
]]>
    </programlisting>
    &example.outputs.similar;
    <screen>
<![CDATA[
...
]]>
    </screen>
   </example>
  </para>
 </refsect1>

 <refsect1 role="seealso">
  &reftitle.seealso;
  <para>
   <simplelist>
    <member><function>related function name here</function></member>
   </simplelist>
  </para>
 </refsect1>

</refentry>

<!-- Keep this comment at the end of the file
Local variables:
mode: sgml
sgml-omittag:t
sgml-shorttag:t
sgml-minimize-attributes:nil
sgml-always-quote-attributes:t
sgml-indent-step:1
sgml-indent-data:t
indent-tabs-mode:nil
sgml-parent-document:nil
sgml-default-dtd-file:"../../../../manual.ced"
sgml-exposed-tags:nil
sgml-local-catalogs:nil
sgml-local-ecat-files:nil
End:
vim600: syn=xml fen fdm=syntax fdl=2 si
vim: et tw=78 syn=sgml
vi: ts=1 sw=1
-->
