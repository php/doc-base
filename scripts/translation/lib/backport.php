<?php

// This file documents and sets some back ported behaviors
// of actual revcheck.php, that may be considered incorrect
// by now, and may be revised after code deduplication.
//
// In other words, this is a big TODO file, documented
// as constants

// Constants

const STATUS_COUNT_MISMATCH = true;
const LOOSE_SKIP_REVCHECK = true;
const FIXED_SKIP_REVCHECK = true;

// Documentation

/* # STATUS_COUNT_MISMATCH

The actual revcheck counts "outdated" files in two different ways;

- Only TranslatedOld:
  https://github.com/php/doc-base/blob/747c53bf8ec72f27ac1a83ba91bcc390eea2e46a/scripts/revcheck.php#L603
- TranslatedOld + RevTagProblem:
  https://github.com/php/doc-base/blob/747c53bf8ec72f27ac1a83ba91bcc390eea2e46a/scripts/revcheck.php#L134

This causes a mismatchs between translators totals and file summary.

To make the mismatch smaller, the "wip" column in Translators was
changed to "misc", and so any status other than "ok" and "old"
was added here.

Also, NotInEnTree is missing on first case, and files
in this situation goes uncounted.

Also, RevTagProblem is counted towards as Old, but files
are show in revtag missing/problem list, and is
impossible to generate diffs with invalid hashes... */

assert( STATUS_COUNT_MISMATCH || ! STATUS_COUNT_MISMATCH );

/* # LOOSE_SKIP_REVCHECK

Consider the output of: git show f80105b4fc1196bd8d5fecb98d686b580b1ff65d

```
commit f80105b4fc1196bd8d5fecb98d686b580b1ff65d

Remove constant tag from literal values (#3251)

* Remove constant tag from literal values

* [skip-revcheck] Fix whitespace

diff --git a/appendices/filters.xml b/appendices/filters.xml
index 59a4735de1..06e0a7276e 100644
--- a/appendices/filters.xml
+++ b/appendices/filters.xml
@@ -302,7 +302,7 @@ fclose($fp);
<parameter>window</parameter> is the base-2 log of the compression loopback window size.
Higher values (up to 15 -- 32768 bytes) yield better compression at a cost of memory,
while lower values (down to 9 -- 512 bytes) yield worse compression in a smaller memory footprint.
- Default <parameter>window</parameter> size is currently <constant>15</constant>.
+ Default <parameter>window</parameter> size is currently <literal>15</literal>.

<parameter>memory</parameter> is a scale indicating how much work memory should be allocated.
Valid values range from 1 (minimal allocation) to 9 (maximum allocation). This memory allocation
diff --git a/install/fpm/configuration.xml b/install/fpm/configuration.xml
index 9baaf43d6f..a34700ef97 100644
--- a/install/fpm/configuration.xml
+++ b/install/fpm/configuration.xml
@@ -805,109 +805,109 @@
<tbody>
<row>
<entry>
- <constant>%C</constant>
+ <literal>%C</literal>
</entry>
<entry>%CPU</entry>
</row>
<row>
```

This commit must be tracked in translations? In other words, this commit
should mark the various files changed as outdated in translations?

The current implementation on doc-base/revcheck.php would *ignore* this
commit, *not* marking these files as outdated on translations.

This is because the code searches for '[skip-revcheck]' in any position [1],
and in any lile [2] of commit messages.

[1] https://github.com/php/doc-base/blob/84532c32eb7b6d694df6cbee3622cec624709654/scripts/revcheck.php#L304
[2] https://github.com/php/doc-base/blob/84532c32eb7b6d694df6cbee3622cec624709654/scripts/revcheck.php#L302

The problem, here, is that this commit on doc-en was squashed, and by so, all
individual commit messages are concatenated in one commit message.

```
Remove constant tag from literal values (#3251)
* Remove constant tag from literal values
* [skip-revcheck] Fix whitespace
```

The solution proposed is to check for '[skip-revcheck]' mark only at the
starting of the first line on commit messages, so future squashed commits
do not cause file modifications being untracked on translations.

After code deduplication, open an issue to consider having an
*strick* [skip-revcheck] mode, avoids the issue above,
by removing any mentions of LOOSE_SKIP_REVCHECK constante. */

assert( LOOSE_SKIP_REVCHECK || ! LOOSE_SKIP_REVCHECK );

/* # FIXED_SKIP_REVCHECK

Consider the output of: git log --oneline -- reference/ds/ds.deque.xml
```
4d17 [skip-revcheck] Convert class markup to be compatible with DocBook 5.2
6cec [skip-revcheck] Normalize &Constants; and &Methods; usage (#2703)
b2a2 These should include the namesapce
120c Document ArrayAccess in PHP-DS
```

The last two commits, each one, will mark all their included files as old
in translations, as the commit message does not contain '[skip-revcheck]'.

The commit 6cec, marked [skip-revcheck], will not mark any file as outdated.

The commit 4d17, marked [skip-revcheck], will mark all its files as outdated,
needing to be updated to 6cec.

See the difference in behaviour between two individual commits marked
'[skip-revcheck]'?
That 6cec commit is also marked '[skip-revcheck]' is
incidental. This discrepancy occurs in any sequence of commits
marked '[skip-revcheck]'.

When the revcheck code, as now, detects that the topmost commit hash contains an
'[skip-revcheck]', it ignores this topmost commit hash, and then selects the fixed
'-2' commit hash as a base of comparison, when the file is calculated as old.

See:
- Oldness test: https://github.com/php/doc-base/blob/84532c32eb7b6d694df6cbee3622cec624709654/scripts/revcheck.php#L362
- Topmost skip: https://github.com/php/doc-base/blob/84532c32eb7b6d694df6cbee3622cec624709654/scripts/revcheck.php#L380
- Hash -2:      https://github.com/php/doc-base/blob/84532c32eb7b6d694df6cbee3622cec624709654/scripts/revcheck.php#L384

The output of -2 test, as now, is:
```
4d17b7b4947e7819ff5036715dd706be87ae4def
6ceccac7860f382f16ac1407baf54f656e85ca0b
```

The code linked above splits the results on the new line, and compares the revtag
hash against the second line, 6cec in this case. But 6cec is itself marked as an
'[skip-revcheck]'. So an [skip-revcheck] is bumping all its file hashes into
another [skip-revcheck] commit hash...

The proposed solution is to removing the use of the fixed -2 topmost hash when
the topmost hash is marked [skip-revcheck] into ignoring any topmost commit
hash marked [skip-revcheck], and thus selecting as an alternative comparison
hash the first topmost hash not marked as [skip-revcheck].

In this case, b2a2.

So any future sequence of [skip-revcheck] commits does not cause the bumping
of hashes in all translations in the presence of a sequence of [skip-revcheck]
commits.

After code deduplication, open an issue to consider having an
multi skipping [skip-revcheck] mode, avoids the issue above,
by removing any mentions of FIXED_SKIP_REVCHECK constante. */

assert( FIXED_SKIP_REVCHECK || ! FIXED_SKIP_REVCHECK );