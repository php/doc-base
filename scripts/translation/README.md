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

See `MIGRATION.md` for procedures and breaking changes expected
when migrating from previous `revcheck.php`.
