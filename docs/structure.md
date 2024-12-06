# Documentation structure

The PHP Manual sources are stored in Git repositories.

To checkout the PHP Manual sources, follow the steps in [Setting up a documentation environment](local-setup.md)

## File structure
**Note for translators:** if any of the source files don't exist in your translation, the English content will be used
during the building process. This means that you *must not* place untranslated files in your translation tree. Otherwise,
it will lead to a mess, confusion and may break some tools.

The structure of the manual sources is hopefully rather intuitive. The most
complicated part is the documentation for extensions, which is also the biggest
part of the manual as all functions are grouped into extensions.

The documentation for extensions is located in `reference/extension_name/`.  For example,
the calendar extension documentation exists in  `reference/calendar/`. There you'll find several files:
- *book.xml* - acts as the container for the extension and contains the preface. Other files (like examples.xml)
are included from here.
- *setup.xml* - includes setup, install and configuration documentation
- *constants.xml* - lists all constants that the extension declares, if any
- *configure.xml* - usually this information is in setup.xml, but if the file exists it is magically
included into setup.xml
- *examples.xml* - various examples
- *versions.xml* - contains version information for the extension
- *foo.xml* - example, foo can be anything specific to a topic. Just be sure to include via book.xml.

A procedural extension (like calendar) also has:
- *reference.xml* - container for the functions, rarely contains any info
- *functions/* - folder with one XML file per function that the extension declares

And OO extensions (such as imagick) contain:
- *classname.xml* - container for the methods defined by the class, contains also basic info about it
- *classname/* - folder with one XML file per method that the class declares

Note: *classname* is the lowercased name of the class, not a literal file or directory name.

There are some other important files:
- *language-defs.ent* - contains local entities used by this language. Some common ones are
  the main part titles, but you should also put entities used only by this language's files here.
- *language-snippets.ent* - longer often used XML snippets translated to this language.
  Including common warnings, notes, etc.
- *translation.xml* - this file is used to store all central translation info, like a small
  intro text for translators and the persons list. This file is not present in the English tree.

## `xml:id` structure

The PHP Manual is a complex document that uses a lot of `xml:id` for anchoring,
linking and XInclude purposes, so some care is necessary when dealing with
them. The pseudo-types of `xml:id` used in manual are:

* **Structural IDs**. IDs that are defined in structural level DocBook
elements, like <chapter>`, `<section>`, etc.

* **XInclude IDs**. IDs that are defined in some other elements, to be targeted
by `<xi:include>` functionality.

Structural IDs use the `id.id` pattern, while XInclude IDs use the
`structural.id..local.ṕath` pattern. That is, Structural IDs only use one dot
as separator, while XInclude IDs are composed of the one existing Structural ID
as prefix, an `..` separator, and a local path suffix.

No `xml:id` can be defined twice in source XMLs. Yet, it is possible that
XInclude functionality generates duplicated IDs while building manuals, as
libxml2 does *not* implement XIncludes 1.1. The `configure.php` script strips
these generated duplicated IDs automatically, but manual editors should strive
to avoid generated duplicated IDs by using XInclude that `xpointer`to XInclude
IDs instead of proper XPointer/XPaths.
