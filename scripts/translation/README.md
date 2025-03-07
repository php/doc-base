# Scripts to check consistency of manual translations

After a normal `doc-base/configure.php --with-lang=$LANG`, it is possible to
run the command line tools below to check translated source files
for inconsistencies. These tools check for structural differences
that may cause translation build failures or non-validating DocBook XML
results, and fixing these issues will help avoid build failures.

Some checks are less structural, and as not all translations are identical,
or use the same conventions, they may not be entirely applicable in all
languages. Even two translators working on one language may have different
opinions on how much synchronization is wanted, so not all scripts will be of
use for all translations.

Because of the above, it's possible to silence each alert indempendly. These
scripts will output `--add-ignore` commands that, if executed, will omit the
specific alerts in future executions.

## broken.php

`doc-base/scripts/broken.php` will test if individual XML files are
ill-formed. That is, if a file contains Unicode BOM, carriage returns (CR),
or if XML contents are not
[well-balanced](https://www.w3.org/TR/xml-fragment/#defn-well-balanced).

Unbalanced XML contents are invalid XML and will result in a broken build.
BOM and CR marks may not result in broken builds, but *will* cause several
tools below to misbehave, as `libxml` behaviour changes if XML text contains
these bytes.

## qaxml-attributes.php

`doc-base/scripts/translation/qaxml-attributes.php` checks if all translated
files have the same tag-attribute-value triplets. Tag's attributes are
extensively utilized in manual for linking and XIncludes. Translated files
with missing or mistyped attributes may cause build failures or missing parts.

This script accepts an `--urgent` option, to filter alerts related to `xml:id`
attributes. This will help translators on languages that are failing to build,
to focus on mismatches that are probably most related with build fails.

## qaxml-entities.php

`doc-base/scripts/translation/qaxml-entities.php` checks if all translated
files contain the same XML Entities References as the original files.
Unbalanced entities may indicate mistyped or wrongly translated parts. This
is problematic because some of these entities are "file
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

## qaxml-pi.php

`doc-base/scripts/translation/qaxml-pi.php` checks if all translated files have
the same processing instructions (PI) as the original files. Unbalanced PIs may
cause compilation errors, as they are utilized in the manual build process.

## qaxml-tags.php

`doc-base/scripts/translation/qaxml-tags.php` checks if all translated files
have the same tags as the original files. Different number of tags between
source texts and translations indicated mismatched translated texts, and may
cause compilation errors

This script accepts an `--detail` option, that will print lines of each
mismatched tag, to facilitate the work on big files.

This script also accepts an `--content=` option, that will check the
*contents* of tags, to inspect tags where the contents are expected *not* to
be translated. Example below.

## qaxml-ws.php

`doc-base/scripts/translation/qaxml-ws.php` inspect whitespace usage inside
some known tags. Spurious whitespace may break manual linking or generate
visible artifacts.

## qaxml-revtag.php

`doc-base/scripts/translation/qaxml-revtag.php` checks if all translated
files have valid [revision tags](https://doc.php.net/guide/translating.md).
Files without revision tags in expected format will fail to generate pretty
diffs on [Translation status](https://doc.php.net/revcheck.php) website or
locally generated `revcheck.php` status pages.

## Suggested execution

The first execution of these scripts may generate an inordinate amount of
alerts. It's advised to initially run each command separately, and work the
alerts on a case by case basis. After all interesting cases are fixed,
it's possible to rerun the command and `grep` the output for `--add-ignore`
lines, run these commands, and by so, mass ignore the residual alerts.

Structural checks:

```
php doc-base/scripts/broken.php
php doc-base/scripts/translation/qaxml-revtag.php

php doc-base/scripts/translation/qaxml-attributes.php
php doc-base/scripts/translation/qaxml-entities.php
php doc-base/scripts/translation/qaxml-pi.php
php doc-base/scripts/translation/qaxml-tags.php --detail
php doc-base/scripts/translation/qaxml-ws.php
```

Tags where is expected no translations:

```
php doc-base/scripts/translation/qaxml-tags.php --content=acronym
php doc-base/scripts/translation/qaxml-tags.php --content=classname
php doc-base/scripts/translation/qaxml-tags.php --content=constant
php doc-base/scripts/translation/qaxml-tags.php --content=envar
php doc-base/scripts/translation/qaxml-tags.php --content=function
php doc-base/scripts/translation/qaxml-tags.php --content=interfacename
php doc-base/scripts/translation/qaxml-tags.php --content=parameter
php doc-base/scripts/translation/qaxml-tags.php --content=type
php doc-base/scripts/translation/qaxml-tags.php --content=classsynopsis
php doc-base/scripts/translation/qaxml-tags.php --content=constructorsynopsis
php doc-base/scripts/translation/qaxml-tags.php --content=destructorsynopsis
php doc-base/scripts/translation/qaxml-tags.php --content=fieldsynopsis
php doc-base/scripts/translation/qaxml-tags.php --content=funcsynopsis
php doc-base/scripts/translation/qaxml-tags.php --content=methodsynopsis
```

Tags where is expected few translations:

```
php doc-base/scripts/translation/qaxml-tags.php --content=code
php doc-base/scripts/translation/qaxml-tags.php --content=computeroutput
php doc-base/scripts/translation/qaxml-tags.php --content=filename
php doc-base/scripts/translation/qaxml-tags.php --content=literal
php doc-base/scripts/translation/qaxml-tags.php --content=varname
```

---

## Old tools (below)

Document below is the previous version of these tools. These tools are
deprecated, and scheduled for remotion very soon.


These old tools needed to be separated configured, before use:
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
