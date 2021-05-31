
# Read this first

This directory contains source files and a setup for converting
PHP's XML documentation into presentation formats like HTML and
RTF. You should not have to bother with this unless you are
writing documentation yourself, or if you simply are curious
about how the XML stuff works.

If you just want to read the documentation, look at:
https://www.php.net/docs.php

# How to write phpdoc files

If you are interested in information about how to
set up the tools needed, how to work with Git and
DocBook on Linux or Windows, or what conventions you
should follow when writing phpdoc files, please refer
to the PHP Documentation HOWTO.

You can read the HOWTO online at: http://doc.php.net/tutorial/

If you are already working with the phpdoc module,
then you can find its XML source in the howto directory
of the module, and build it yourself with:

```bash
phd -d .manual.xml
```

However, PhD is a separate project which can be read about here:
https://wiki.php.net/doc/phd

## Quick reference

### Source checkout

For a more general git-workflow see [the Wiki](https://wiki.php.net/vcs/gitworkflow#reviewing_and_closing_pull_requests).
Make sure to upload your SSH public key to your account at main.php.net

Check out the sources:

```bash
mkdir phpdoc
cd phpdoc
git clone git@github.com:php/doc-en.git en
git clone git@github.com:php/doc-your-language-of-choice.git your-language-of-choice
git clone git@github.com:php/doc-base.git
```

Change `your-language-of-choice` if you would like to check out a different language.

The `en` folder contains the English DocBook source files, and `doc-base` contains tools
and resources used in all languages.

### Edits

* Make the change.  Use spaces not tabs.  Be sure to carefully watch your whitespace!
* cd into the desired clone directory, e.g.
  ```bash
  cd en
  ```
* Look at your unified diff, make sure it looks right and that whitespace changes aren't mixed in:
  ```bash
  git diff path/to/file.xml
  ```
* Make sure no errors are present, so at the command line in your phpdoc source directory run:
  ```bash
  php ../doc-base/configure.php
  ```
* If you are translating, remember to add the full Git commit hash of the English file that you are translating
  from, to the file's `EN-Revision` comment.
* Commit your changes
  ```bash
  git commit path/to/file.xml
  ```

Read the HOWTO for more information.  After reading the HOWTO,
email the phpdoc mailing list (phpdoc@lists.php.net) with questions
and concerns.

### New functions

* Copy an existing XML file or use a skeleton from the HOWTO.
  Rename and place it into the appropriate directory.
* Edit.  Be sure no leftover text exists.  No tabs either.
* If you are translating, remember to add the full Git commit hash of the English
  file that you are translating from, to the file's `EN-Revision` comment.
* cd into the desired clone directory, e.g.
  ```bash
  cd en
  ```
* Now test locally before commit by first running
  ```bash
  php ../doc-base/configure.php
  ```
* Add the file to your staging area
  ```bash
  git add path/to/yourfile.xml
  ```
* Commit the file and push it to the git-server
  ```bash
  git commit path/to/yourfile.xml
  git push remote your-branch
  ```
* Open a pull request to the main repository via GitHub

### Some popular tags and entities

    <filename>          filenames
    <constant>          constants
    <varname>           variables
    <parameter>         a function's parameter/argument
    <function>          functions, this links to function pages or bolds if
                        already on the function's page.  it also adds ().

    <literal>           teletype/mono-space font <tt>
    <emphasis>          italics
    <example>           see HOWTO, includes many other tags.
    <link>              internal manual links
                        <link linkend="language.variables">variables</link>

    <link>              external links via global.ent
                        <link xlink:href="&spec.cookies;">mmm cookies</link>

    <type>              types, this links to the given types manual
                        page: <type>object</type> -> php.net/types.object

    &return.success;    see: language-snippets.ent
    &true;              <constant>TRUE</constant>
    &false;             <constant>FALSE</constant>
    &php.ini;           <filename>php.ini</filename>

Be sure to check out [global.ent](entities/global.ent) and
language-snippets.ent (located within each language's repo) for
more information for entities and URLs.

# Quality Assurance Tools (QA Tools)

There are various scripts available to ensure the quality of the documentation
and find issues with it, they are located in the `scripts/qa/` directory.

There might be some more just in `scripts/` but they need to be checked if they
are still relevant and/or given some love.

