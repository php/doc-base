<?php

const VALUE_ERROR_BETWEEN_ERROR_SECTION = <<<'CONTENT'
<simpara>
 Lance une <exceptionname>ValueError</exceptionname> si <parameter>PARAMETER_NAME</parameter>
 est moins que MIN_TAG ou plus grand que MAX_TAG.
</simpara>
CONTENT;

const VALUE_ERROR_BETWEEN_CHANGELOG = <<<'CONTENT'
<row>
 <entry>VERSION</entry>
 <entry>
  Une <exceptionname>ValueError</exceptionname> est désormais lancé si
  <parameter>PARAMETER_NAME</parameter> est moins que MIN_TAG ou plus grand que MAX_TAG
 </entry>
</row>
CONTENT;
