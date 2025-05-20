# Frequently Asked Questions

## I'm about to document a new PHP extension. How should I start?
Change your working directory to `phpdoc/doc-base/scripts/docgen/` and execute:
```sh
php docgen.php -e simplexml -o outdir
```

It creates the skeletons that you edit, and then commit.

Help is available with following command: `php docgen.php -h`.

## I created skeletons that contain a bunch of default text, should I commit it?
No! Edit the files, to check the generated content and add more information
before committing. Thinking that it is okay to commit the skeleton files because
you will soon come along and flesh them out might seem like a good idea. However,
temporary often becomes permanent.

## Is there an online editor?
No, but [simple changes can be submitted via GitHub](contributing#minor-changes).

## How do I add a link to a method?
Use `<methodname>Class::method</methodname>`. Note that the case does not matter when adding a link.

## If a refentry should not emit versioning information, what should I do?
Add the `role="noversion"` to its `<refentry>`. Example:
```xml
<refentry xml:id="reserved.variables.argc" xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" role="noversion">
```

## How do I add an external link to the documentation?
All external links are added to `doc-base/entities/global.ent`. Markup looks as follows:
```xml
<!ENTITY url.google "http://www.google.com/">
```
Then you can use this syntax in the documentation:
```xml
Check out <link xlink:href="&url.google;">Google</link>
```
Be sure the file understands the `xlink` namespace, by using `xmlns:xlink="http://www.w3.org/1999/xlink"` in the document element.

## When adding a note, should I add a title?
Typically titles are useful for notes, but it's not required.
```xml
<note>
 <title>Foo</title>
 <para>Note contents are here.</para>
</note>
```

## A feature became available in PHP X.Y.Z, how do I document that?
Version information for functions is stored inside `versions.xml` within
each extension (e.g. `phpdoc/en/extname/versions.xml`). Changes to functions,
like added parameters, are documented within the changelog section for each page.

## A parameter is optional, how is it documented?
Like normal, except `methodparam` receives the `choice="opt"` attribute, and
the `<initializer>` tag is used to signify the default value.
```xml
<methodparam choice="opt">
 <type>bool</type>
 <parameter>httponly</parameter>
 <initializer>false</initializer>
</methodparam>
```

## I see example.outputs and example.outputs.similar entities, what's the difference?
The `&example.outputs.similar;` entity is used when the output may differ between executions or machines.
The `&example.outputs;` entity output will always, under all conditions, be the same.

## I need to add a piece of text to three or more pages, how?
Add the snippet to `en/language-snippets.ent` as an entity and link to the entity within the desired pages.
This is done so translators can update one version of this text.

## How do I find missing documentation?
Missing functions (no associated XML files) can be found like so (assuming a doc checkout, and PhD is installed):
```sh
php doc-base/configure.php
phd --docbook doc-base/.manual.xml --package PHP --format php
php doc-base/scripts/check-missing-docs.php -d output/index.sqlite
```
