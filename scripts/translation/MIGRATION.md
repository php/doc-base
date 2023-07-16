
# Migration

## Old vs critical

This revcheck will now emit critical status for files outdated for more than 30 days.

## Maintainers with spaces

The regex on `RevtagParser` was narrowed to not accept maintainer's names
with spaces. This need to be confirmed on all active translations, or
the regex modified to accept spaces again.

## en/chmonly

`en/chmonly` is ignored on revcheck, but it appears translatable. If it's a
`en/` only directory, this should be uncommented on RevcheckIgnore.
