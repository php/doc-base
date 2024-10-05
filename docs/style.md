# Style guidelines

## Technical requirements
- All files **must** be encoded using UTF-8 (without BOM)
- Use only Unix line endings (`\n`)

## Line lengths
Please aim to keep lines in an XML file around 80 characters long or less.
This is a loose requirement and 100 is probably acceptable as a maximum length.
This aids in keeping *diffs* simple, which is particularly useful for translators,
so follow this rule carefully.

## Whitespaces
For XML, indent using one space. Do not use tabs. PHP code, in examples, uses
four spaces for indentation, since the code should adhere to the [PEAR Coding Standards](http://pear.php.net/manual/en/standards.php).

## Punctuation
Punctuation in the PHP Manual follows regular grammatical rules. When writing flowing sentences, such as in function
descriptions, normal punctuation should be used. Lists, titles, and sentence fragments should not be punctuated with
a period. Sentences need not have two spaces between them. Commas and apostrophes should be used appropriately.

## Personalization
The PHP Manual is a technical document, and should be written so. The use of "you" is rampant in the manual,
and presents an unprofessional image.  The only exceptions to the personalization rule are: the PHP Tutorial and FAQs.

Example:
```
INCORRECT: You can use the optional second parameter to specify tags that should not be stripped.
CORRECT: The optional second parameter may be used to specify tags that should not be stripped.
```

## Chronology
- When referring to a specific version of PHP, "since" should not be used. "As of" should be used in this case.
- In changelogs, newer PHP versions go above older ones.
- If a changelog entry applies to multiple PHP versions, separate them by a comma with the lower version first.
Example: `<entry>5.2.11, 5.3.1</entry>`

## General Grammar
The PHP Manual should be written with particular attention to general English grammar. Contractions should be used
appropriately. Special attention should be applied to sentence construction when using prepositions (i.e., sentences
should not end in prepositions).

## PHP Manual Terms
Various non-english, technical terms are used throughout the PHP Manual, without clear indication of their appropriate
spelling. The following list clears up this issue:

Appropriate Use          | Inappropriate Use(s)
-------------------------|--------------------------------------------
any way                  | anyway, anyways
appendices               | appendixes
built-in                 | built in, builtin
email                    | e-mail
[example.com][example]   | php.net, google.com
extension                | module
Linux                    | linux, *n*x, *nix, *nux, etc
PHP 8                    | PHP8, PHP-8
PHP 8.3.0                | PHP 8.3, PHP 8.3.0RC2, PHP 8.0.0BETA, PHP 8.3.0PL1
superglobals             | super globals, autoglobals
web server               | webserver
the [Foo Page][example]  | [click here][example], go [here][example]
Unix                     | UNIX (it's a registered trademark)
Windows                  | windows (when referring to Microsoft Windows)
macOS                    | MacOS, Mac OS X

[example]: http://example.com
