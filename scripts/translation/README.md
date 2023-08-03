# Some useful scripts for maintaining translation consistency of manual

Some of these scripts only test some file contents or XML structure
of translated files against their equivalents on `en/` directory.
Others will try modify the translations in place, changing the
translated files. Use with care.

Not all translations are identical, or use the same conventions.
So not all scripts will be of use for all translations. The
assumptions of each script are described in each file.

The `lib/` directory contains common code and functionality
across these scripts.

Before using the scritps, it need be configured:
```
php doc-base/scripts/translation/configure.php $LANG_DIR
```

## qaxml.a.php

`qaxml.a.php` checks if all updated translated files have
the same tag-attribute-value triples. Tag's attributes are extensively
utilized in manual for linking and XIncluding. Translated files with
missing os mistyped attributes may cause build failing or missing
parts not copied by XIncludes.

# Migration

## No critical

This revcheck will not emit a critical status, for files outdated for more
than 30 days, as this was silently removed on
<https://github.com/php/doc-base/commit/8f757b4fe281f5b00bd8bfe9cc1799fb0ce27822>

There is a new `days` property that tracks how old a file is in days, to make
decisions on coloring revcheck or deprecating translations.

## Maintainers with spaces

The regex on `RevtagParser` was narrowed to not accept maintainer's names
with spaces. This need to be confirmed on all active translations, or
the regex modified to accept spaces again.

## en/chmonly

`en/chmonly` is ignored on revcheck, but it appears translatable. If it's a
`en/` only directory, this should be uncommented on RevcheckIgnore.
