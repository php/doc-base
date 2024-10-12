# Coding standard for examples

Examples need to be clear and simple, but should show the possibilities and
usage of the functions used.  Only use OOP features where you would like to
present them, use simple functions in other areas.

References:  http://pear.php.net/manual/en/standards.php
             http://www.php.net/manual/howto/

## Requirements

- When appropriate, use superglobals
- Never generate PHP errors (`E_ALL|E_STRICT` friendly)
- Be short and generic
- Follow the PEAR coding standards

## Program listing roles  (`<programlisting role="xxx">`)
   
PHP examples should always be in `<programlisting role="php">`. Only
PHP examples should have this role.  Other possible roles are:
   
 - c           (C code) 
 - html        (100% XHTML)
 - php         (Some PHP)
 - shell       (commandline, bash, etc)
 - sql         (SQL statements)
 - apache-conf (Apache)

## Titles

When appropriate, it's encouraged to include the function name in
the title, for example:

```xml
<title>A <function>strlen</function> example</title>
```

## Code placement

The contents start at column/row 0 in the example.  For example, this
means your example's content will be flush against the `<![CDATA[` tag.

## PHP tags

Always use long PHP tags (`<?php`) and never short tags (`<?` or `<?=`).

## CDATA

Always use `<![CDATA[ ... ]]>` as this increases the readability of
the examples.  For example, you literally write `<` instead of `&lt;`
inside of CDATA.  Nothing in CDATA is parsed, it's taken literally.
So, you cannot use links, dev-comments, `<function>`, etc.

## Deprecated code

Do not use aliases or deprecated syntax.
   
## Use of newer PHP features

If an example uses features, such as arguments specific to a newer
version of PHP, add a comment that mentions this.  For example:

```php   
   // Second argument was added in PHP 4.1.0
   foo('bar', 'baz');
```
   
If appropriate, show examples that work in older versions of PHP but
do not use reserved function names.  For example, a PHP 4.2.3 version
of `file_get_contents()` should not be named `file_get_contents()`.

## Use of booleans in examples

Do not use entities such as `&true;` in examples but instead write them
out as `TRUE`, `FALSE`, and/or `NULL`.

## Spacing

Never use tabs, only use spaces.  Intention levels are four spaces 
and do not indent the first level of code.  For example:
   
### Good:
```php
<?php
$str = 'Hello World';
function foo($str)
{
   return $str;
}
?>
```
   
### Bad:

```php
<?php
   $str = 'Hello World';
   function foo($str) 
   {
       return $str;
   }
?>
```

## IDs

It is a good idea to add xml:id to the examples. IDs generate anchors and
make it possible to list them in an Example Listing Appendix in the future.


## Error handling:

This section isn't yet complete but there are three main ways to
implement error handling in the PHP manual:

a) Use of the `or` operator.
  
This is okay for development code but not ideal for production as use
of `or` is rather limiting.  An example use:
  
```php
foobar($lname) or die(...);
```

b) A boolean check, along with braces
  
This allows additional expressions inside the braces but requires
more code.  This is the preferred method.  An example use:
     
```php
     if (!foobar($lname)) {
         ...
         exit;
     }
```
     
c) `trigger_error()`
  
There is debate on whether to use `trigger_error()` in the examples so for
now, do not use it (at least until the error handling docs are updated).

## About Variables/Constants/Strings

1. Don't use variables which are not set in examples.

2. Constants should always be all-uppercase.

3. Use single quotes ' when appropriate.

4. For output use `echo`, instead of `print`.

5. Lowercase HTML tags.

6. Variables in strings:

* Strings in strings

This is of course debatable and subject to personal preference.  The two
main methods are inline or concatenation:
    
```php
echo "bar is $bar";
echo "bar is {$bar}";
```
vs
```php
echo 'bar is ' . $bar;
```
   
All of the above methods are acceptable.

* Arrays in strings
   
As constants aren't looked for in strings, the following is fine but
may confuse newbies so it's not to be used in examples:
     
```php
echo "an $array[key] key";
```

Instead, consider these:
     
```php
echo "an {$array['key']} key";
echo 'an ' . $array['key'] . ' key';
```

## How to write...

### Control Structures

See PEAR coding standards

### Functions

#### Function naming

Procedural function names should be lowercase.  If multiple words are
needed in the function name, use a `_`.  Example: `foo_function();`

OOP function names should follow the PEAR Coding Standards which
would be `fooFunction()`.

#### Function calls
#### Function definitions

See PEAR coding standards

### Comments
### Example URLs/Emails

See PEAR coding standards

### Example output

For very short example printouts, use C++ style comment (`//`) on the
line where the output occurs, or in the description above the line:

```php
echo $var; // 32
```

For longer example printouts, there are a couple methods which are
acceptable.  Medium sized output may be inline with the example
itself through use of `/* comments */`, for example:

```php
<?php
$arr = foo();
print_r($arr);

/* Outputs:

Array 
(
    [0] => 'bread'
    [1] => 'peanut butter'
    [2] => 'jam'
)
*/
?>
```

For longer example printouts, use the `<screen>` container in conjunction
with `<![CDATA[...]]>`

```xml
 <refsect1 role="examples">
  &reftitle.examples;
  <example>
   <title>A <function>foo</function> example</title>
   <programlisting role="php">
<![CDATA[
<?php
$arr = bar();
print_r($arr);
?>
]]>
   </programlisting>
   &example.outputs;
   <screen>
<![CDATA[
Array
(
    [0] => 'a';
    [1] => 'b';
    [2] => 'c';
    ...
)
]]>
   </screen>
  </example>
 </refsect1>
```
