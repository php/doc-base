<?php 
require('shared-manual.inc');
sendManualHeaders('ISO-8859-1','zh_tw');
setupNavigation(array(
  'home' => array('index.php', 'PHP ��U'),
  'prev' => array('index.php', 'PHP ��U'),
  'next' => array('getting-started.php', '�J��'),
  'up'   => array('index.php', 'PHP ��U'),
  'toc'  => array(
        array('preface.php', 'Preface'),
     array('getting-started.php', '�J��'),
     array('langref.php', '�y���Ѧ�'),
     array('features.php', '�S��'),
     array('funcref.php', '��ưѦ�'),
      array('streams.php', 'Streams API for PHP Extension Authors'),
     array('faq.php', 'FAQ'),
     array('appendixes.php', '����'),
)));
manualHeader('Preface','preface.php');
?><DIV
CLASS="preface"
><H1
><A
NAME="preface"
></A
>Preface</H1
><BLOCKQUOTE
CLASS="ABSTRACT"
><DIV
CLASS="abstract"
><A
NAME="AEN53"
></A
><P
></P
><P
>&#13;    <SPAN
CLASS="acronym"
>PHP</SPAN
>, which stands for "PHP: Hypertext
    Preprocessor" is a widely-used Open Source general-purpose
    scripting language that is especially suited for Web
    development and can be embedded into HTML. Its syntax draws
    upon C, Java, and Perl, and is easy to learn. The main goal of
    the language is to allow web developers to write dynamically
    generated webpages quickly, but you can do much more with PHP.
   </P
><P
></P
></DIV
></BLOCKQUOTE
><P
>&#13;   This manual consists primarily of a function reference, but also contains a
   language reference, explanations of some of PHP's major features, and other
   supplemental information.
  </P
><P
>&#13;   You can download this manual in several formats at <A
HREF="http://www.php.net/docs.php"
TARGET="_top"
>http://www.php.net/docs.php</A
>.  The downloads are updated as
   the content changes. More information about how this manual is developed
   can be found in the  <A
HREF="about.php"
>'About the manual'</A
>
   appendix.
  </P
></DIV
><?php manualFooter('Preface','preface.php');
?>