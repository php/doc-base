# Style guidelines

The style for the PHP manual has evolved over the years, and this tries
to capture the current guidelines. Existing documentation may not closely
adhere to these, but new additions and substantial changes should.

When updating existing documentation, it is okay to not update
conflicts with these style guidelines if it would cause too much work
for translators.

## Technical requirements
- All files **must** be encoded using UTF-8 (without BOM)
- Use only Unix line endings (`\n`)

## Line lengths
Please aim to keep lines in an XML file around 80 characters long or less.
It is also best to start new sentences on new lines.
These are also known as [Semantic Line Breaks](https://sembr.org).
This aids in keeping *diffs* simple, which is particularly useful for translators.

## Whitespaces
For XML, indent using one space. Do not use tabs.
PHP and other code in examples may use more spaces for indentation, and
should follow [the coding style for examples](cs-for-examples.md).

## Punctuation
Punctuation in the PHP Manual follows regular grammatical rules.
When writing flowing sentences, such as in function descriptions, normal
punctuation should be used.
Lists, titles, and sentence fragments should not be punctuated with
a period.
Sentences need not have two spaces between them.
Commas and apostrophes should be used appropriately.

## Markup

### Use `<para>` sparingly

Use `<simpara>` in markup (similar to HTML's `<p>`) in favor of `<para>`
(similar to HTML's `<div>`) when there are no block elements (such as
`<example>` or `<itemizedlist>` in the paragraph.

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

The PHP Manual should be written with particular attention to general
American English grammar and spelling.

- The [serial (Oxford) comma](https://en.wikipedia.org/wiki/Serial_comma)
should be used in a series of three or more terms.

- Contractions should be used appropriately.

- Special attention should be applied to sentence construction when using
prepositions (i.e., sentences should not end in prepositions).

- If a statement includes a conditional conjunction, the condition being
met should come before the independent clause.
The previous statement is an example of how a conditional conjuction
should be formatted.
See [PR#1565](https://github.com/php/doc-en/pull/1565) for another
example.

## PHP Manual Terms

There are various phrases and technical terms used throughout the manual where
we try to maintain consistent spelling, formatting, and usage, such as:

Appropriate Use          | Inappropriate Use(s)
-------------------------|--------------------------------------------
any way                  | anyway, anyways
appendices               | appendixes
built-in                 | built in, builtin
email                    | e-mail
[example.com][example]   | php.net, google.com
extension                | module
PHP 8                    | PHP8, PHP-8
PHP 8.3.0                | PHP 8.3, PHP 8.3.0RC2, PHP 8.0.0BETA, PHP 8.3.0PL1
superglobals             | super globals, autoglobals
web server               | webserver
the [Foo Page][example]  | [click here][example], go [here][example]
Linux                    | linux, \*n\*x, \*nix, \*nux, etc
Unix                     | UNIX (it's a registered trademark)
Windows                  | windows (when referring to Microsoft Windows)
macOS                    | MacOS, Mac OS X

[example]: http://example.com
