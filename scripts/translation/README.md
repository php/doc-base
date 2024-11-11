
# Useful scripts for maintaining translation consistency of manual

Some of these scripts only test some file contents or XML structure
of translated files against their equivalents on `en/` directory.
Others will try to modify the translations in place, changing the
translated files. Use with care.

Not all translations are identical, or use the same conventions.
Even two translators working on one language may havedifferent
opinions on how much synchronization is wanted. So not all scripts
will be of use for all translations.

Because of aboce, it's possible to silence each alert indempendly.
These scripts will output `--add-ignore` commands that, if executed,
will omit the specific warming in future executions.

The `lib/` directory contains common code and functionality
across these scripts.

Before using the scripts, it need be configured:
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

## Initial alerts execution

The first execution of these scripts may generate an inordinate amount of
alerts. It's advised to initially run each command separately, and work the
alerts on a case by case basis. After all interesting cases are observed,
it's possible to rerun the command, and `grep` the output for `--add-ignore`
lines, and to mass ignore the residual alerts.

