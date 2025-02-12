
# Scripts to check consistency of manual translations

After a normal `doc-base/configure.php --with-lang=$LANG`, it is possible to
run the command line tools below to check individual source files of
translation for inconsenticies. These tools check for structural differences
that may cause translation build failures or non-validating DocBook XML
results, and fixing these issues helps avoid build failures.

Some checks are less structural, and as not all translations are identical,
or use the same conventions, they may not be entirely applicable in all
languages. Even two translators working on one language may have different
opinions on how much synchronization is wanted, so not all scripts will be of
use for all translations.

Because of the above, it's possible to silence each alert indempendly. These
scripts will output `--add-ignore` commands that, if executed, will omit the
specific alerts in future executions.

## First execution

The first execution of these scripts may generate an inordinate amount of
alerts. It's advised to initially run each command separately, and work the
alerts on a case by case basis. After all interesting cases are fixed,
it's possible to rerun the command and `grep` the output for `--add-ignore`
lines, run these commands, and so mass ignore the residual alerts.

## qaxml-attributes.php (structural)

`doc-base/scripts/translation/qaxml-attributes.php` checks if all translated
files have the same tag-attribute-value triplets. Tag's attributes are
extensively utilized in manual for linking and XIncluding. Translated files
with missing or mistyped attributes may cause build failures or missing parts,
not copied by XIncludes.

This script accepts an `--urgent` option, to filter alerts related to `xml:id`
attributes. This will help translators on languages that are failing to build,
to focus on mismatches that are probably most related with build fails.

## qaxml-entities.php (structural)

`doc-base/scripts/translation/qaxml-entities.php` checks if all translated
files contain the same XML Entities References as the original files.
Unbalanced entities may indicate mistyped or wrongly translated parts. This
is particularly problematic because some of these entities are "file
entities", that is, entities that include entire files and even directories,
so missing or misplaced file entity references almost always cause build
failures.

This script accepts an `--urgent` option, to filter alerts related to file
entities. This will help translators on languages that are failing to build,
to focus on mismatches that are probably most related with build fails.

This script also accepts `-entity` options that will ignore the informed
entities when generating alerts. This is handy in languages that use some
"leaf" entities differently than `doc-en`. For example, `doc-de` uses a lot of
`&zb;` and `&dh;` entities, and could run with `-zb -dh` to avoid generating
alerts for these entities' differences.

## Old tools (below)

The tools on `doc-base/scripts/translation/` are slowly being rewritten. While
this effort is not complete, the previous tools, document below, could be used
to supply for features yet not completed.

---

Before using the old scripts, they need be configured:
```
php doc-base/scripts/translation/configure.php $LANG_DIR
```

## qarvt.php

`qarvt.a.php` checks if all translated files have revtags in the
expected format.

## qaxml.a.php

`qaxml.a.php` checks if all updated translated files have
the same tag-attribute-value triplets. Tag's attributes are extensively
utilized in manual for linking and XIncluding. Translated files with
missing or mistyped attributes may cause build failing or missing
parts, not copied by XIncludes.

## qaxml.e.php

`qaxml.e.php` checks if all updated translated files have
the same external entities as the original files. Unbalanced entities
may indicate mistyped or wrongly translated parts.

## qaxml.p.php

`qaxml.p.php` checks if all updated translated files have
the same processing instructions as the original files. Unbalanced PIs
may cause compilation errors, as they are utilized in the manual build
process.

## qaxml.t.php

`qaxml.t.php` checks if all updated translated files have
the same tags as the original files. Different number of tags between
source texts and target translations may cause compilation errors.

Usage: `php qaxml.t.php [--detail] [tag[,tag]]`

`[tag[,tag]]` is a comma separated tag list to check their
contents, as some tag contents are expected *not* be translated.

`--detail` will also print line definitions of each mismatched tag,
to facilitate bitsecting.

## Suggested execution

Structural checks:

```
php doc-base/scripts/translation/configure.php $LANG_DIR

php doc-base/scripts/translation/qarvt.php

php doc-base/scripts/translation/qaxml.a.php
php doc-base/scripts/translation/qaxml.e.php
php doc-base/scripts/translation/qaxml.p.php
php doc-base/scripts/translation/qaxml.t.php
php doc-base/scripts/translation/qaxml.w.php
```
Tags where is expected no translations:
```
php doc-base/scripts/translation/qaxml.t.php acronym
php doc-base/scripts/translation/qaxml.t.php classname
php doc-base/scripts/translation/qaxml.t.php constant
php doc-base/scripts/translation/qaxml.t.php envar
php doc-base/scripts/translation/qaxml.t.php function
php doc-base/scripts/translation/qaxml.t.php interfacename
php doc-base/scripts/translation/qaxml.t.php parameter
php doc-base/scripts/translation/qaxml.t.php type
php doc-base/scripts/translation/qaxml.t.php classsynopsis
php doc-base/scripts/translation/qaxml.t.php constructorsynopsis
php doc-base/scripts/translation/qaxml.t.php destructorsynopsis
php doc-base/scripts/translation/qaxml.t.php fieldsynopsis
php doc-base/scripts/translation/qaxml.t.php funcsynopsis
php doc-base/scripts/translation/qaxml.t.php methodsynopsis
```
Tags where is expected few translations:
```
php doc-base/scripts/translation/qaxml.t.php code
php doc-base/scripts/translation/qaxml.t.php computeroutput
php doc-base/scripts/translation/qaxml.t.php filename
php doc-base/scripts/translation/qaxml.t.php literal
php doc-base/scripts/translation/qaxml.t.php varname
```
