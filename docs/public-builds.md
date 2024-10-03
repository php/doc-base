# The PHP Manual builds

The PHP Manual is written in [DocBook][docbook] and built by [PhD][phd], and
these builds are rsynced to the web servers for users to use.

## Mirror builds
The [rsync box][rsync.php.net] builds the manuals every night, at around 05:00 UTC.
The web servers then pick up these builds when they sync, which usually happens every hour.

## Doc server builds
The [docs development server][docs.php.net] builds the manual six times a day
(every 4 hours, starting at midnight UTC). This takes place on the [euk2][euk2] server.
An easy way to see when each translation was last built, is to look at the
[doc downloads page with dates][download-docs]. Also note that several old
translations reside on this particular server, as it attempts to build every
translation (both active and inactive).

## CHM builds
The CHM version of the manual is built on a Windows machine and pulled on Fridays,
for distribution to web servers. [Richard][rquadling] maintains these builds.

## Validation
Aside from running `php configure.php -–with-lang=foo` (see [editing](editing.php))
for a language, another way to check if the docs validated is by looking at build
dates on the doc server. See "Doc server builds", above.

## Additional notes
- If a manual does not validate on some day, it will not be pushed to the web servers
  until it does validate (hopefully, the next day).
- Only active translations are built on rsync box (and then pushed to regular
  mirrors). This is managed in [web/php/includes/languages.inc][languages.inc]
- [docs.php.net][docs.php.net] attempts to build all translations (both active
  and inactive). However, we use a `broken-language.txt` file in root of broken
  translations to disable those that are very outdated and failing to build
  for a long time.

## The humans who manage these
If there is a problem with the synced builds, it's wise to contact
[Derick][derick] or [Hannes][bjori].

[docbook]: http://www.docbook.org/
[phd]: http://doc.php.net/phd.php
[rsync.php.net]: https://wiki.php.net/systems/sc2
[docs.php.net]: http://docs.php.net
[euk2]: https://wiki.php.net/systems/euk2
[download-docs]: http://docs.php.net/download-docs.php?sizes=1
[fetch-chms]: https://github.com/php/doc-base/blob/master/scripts/fetch-chms.php
[languages.inc]: https://github.com/php/web-php/blob/master/include/languages.inc
[rquadling]: http://people.php.net/rquadling
[derick]: http://people.php.net/derick
[bjori]: http://people.php.net/bjori
[salathe]: http://people.php.net/salathe
[phpdoc-list]: mailto:phpdoc@lists.php.net
