<?php

if (!defined('VALUE_ERROR_BETWEEN_ERROR_SECTION')) {
    define('VALUE_ERROR_BETWEEN_ERROR_SECTION', <<<'CONTENT'
<simpara xmlns="http://docbook.org/ns/docbook">
 Throws a <exceptionname>ValueError</exceptionname> if <parameter>PARAMETER_NAME</parameter>
 is less than MIN_TAG or greater than MAX_TAG.
</simpara>
CONTENT
    );
}

if (!defined('VALUE_ERROR_BETWEEN_CHANGELOG')) {
    define('VALUE_ERROR_BETWEEN_CHANGELOG', <<<'CONTENT'
<row xmlns="http://docbook.org/ns/docbook">
 <entry>VERSION</entry>
 <entry>
  A <exceptionname>ValueError</exceptionname> is now thrown if
  <parameter>PARAMETER_NAME</parameter> is less than MIN_TAG
  or greater than MAX_TAG
 </entry>
</row>
CONTENT
    );
}
