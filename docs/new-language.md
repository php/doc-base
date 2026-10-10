# Starting a new translation

This chapter is for people who want to translate the PHP Manual into a
language that is not yet available at <https://www.php.net/docs.php>.
It describes how to get from "I would like to translate the manual" to an
official `php/doc-{LANG}` repository that is built and published on php.net.

If your language already exists, you do not need this chapter: read
[Translating documentation](translating.md) and [Joining the team](joining.md),
and write to the `doc-{LANG}@lists.php.net` mailing list.

## Before you start

### Check whether the language already exists

The list of known translations is kept in
[`languages.php`](https://github.com/php/doc-base/blob/master/languages.php)
in the doc-base repository. Some of these translations are *inactive*: their
repository exists, but the manual is not built or not listed on php.net because
nobody is maintaining it. If your language is in that list, the right way to
continue is to revive the existing `php/doc-{LANG}` repository, not to start a
new one. Write to `doc-{LANG}@lists.php.net` (or `phpdoc@lists.php.net` if the
language list is silent) and ask to be added to the translation team.

### Team up

Translating the PHP Manual is a large and long-running effort. The manual
contains more than ten thousand files, and it changes every day. A translation
that is started by one person alone rarely reaches a state where it can be
published, and even then it needs several people to keep it up to date.

Before you create a repository, search the
[phpdoc mailing list archive](https://news-web.php.net/php.doc) for earlier
announcements of a translation into your language. It happens regularly that two
people start the same language a few months apart without knowing of each
other. If somebody announced your language before you, reply to that thread
and join their repository instead of starting a second one. An official
translation is expected to have at least two active maintainers.

### Choose the language code

Use the lowercase two-letter [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)
code of the language, for example `fa`, `bn` or `ar`. A regional variant such as
`pt_br` is only used when the distinction matters for the translation itself.
The code is used for the repository name (`doc-{LANG}`), the mailing list
(`doc-{LANG}@lists.php.net`) and the manual URL (`php.net/manual/{LANG}/`).

## Announce your intent

Send a message to `phpdoc@lists.php.net`. You have to
[subscribe](https://news-web.php.net/php.doc) to the list before you can post.
Mention:

- the language and the language code you intend to use,
- your GitHub username, and the usernames of anyone working with you,
- a link to your repository if you already have one.

This lets the documentation team point you to existing efforts and answer
questions early. For informal questions there is also a documentation channel on
the [PHP Community Foundation Discord](https://phpc.chat).

## Set up the community repository

Create a repository named `doc-{LANG}` under your own GitHub account. Once the
translation is ready to be published, it will be transferred into the `php`
organization, so there is no need to ask for a repository up front.

The repository mirrors the structure of [php/doc-en](https://github.com/php/doc-en),
which is described in [Documentation structure](structure.md). The rules that
matter most for a new translation:

- **Only translated files exist in the repository.** Any file that is missing
  from your translation is taken from the English manual when the manual is
  built. Untranslated copies of English files make the translation appear
  complete when it is not, and they go stale.
- **Every translated file starts with an `EN-Revision` comment** that records
  which version of the English file it is based on. How to find the commit
  hash and keep it up to date is explained in
  [Translating documentation](translating.md).
- **Three language files are required** at the root of the repository:
  - `language-defs.ent` and `language-snippets.ent`, translated copies of the
    files with the same name in doc-en;
  - `translation.xml`, which does not exist in doc-en. It contains a short
    `<intro>` text about the translation team and a `<translators>` list with
    one `<person>` element per contributor. Take
    [doc-it/translation.xml](https://github.com/php/doc-it/blob/master/translation.xml)
    as an example. The translation status tools on doc.php.net use this file to
    detect the language, so without it no status can be generated.

The workflow for each file is the same as for an existing translation: copy the
file from doc-en, translate it, set the `EN-Revision` comment, commit. Prefer
many small commits over a single large import. Small commits can be reviewed,
and the commit history is what later shows that the translation is maintained.

### Build the translation locally

Follow [Setting up a local build environment](local-setup.md) with your
repository checked out as `{LANG}` next to `doc-base` and `en`. Then run

```
php doc-base/configure.php --with-lang={LANG}
```

and render the result with PhD. A build without validation errors is a
requirement before the translation can be published. Building regularly also
catches broken XML early, while the file that caused it is still fresh in your
mind.

If your language is written right-to-left, build and view the manual locally
to check the rendering. If something is displayed wrongly, open an issue at
<https://github.com/php/phd>.

## Recommended order of translation

Start with the parts of the manual that most readers see, and leave the long
tail of extension reference pages for later:

1. `chapters/`, `faq/`, `features/`, `language/` and `install/`;
2. `reference/` for the extensions that are part of every PHP build, such as
   `reference/strings/`, `reference/array/`, `reference/spl/`,
   `reference/datetime/`, `reference/pdo/`, `reference/mysqli/`,
   `reference/json/` and `reference/mbstring/`;
3. the remaining bundled extensions;
4. third-party (PECL) extensions last.

For a small translation to compare your repository against, look at
[php/doc-it](https://github.com/php/doc-it). For a complete one, look at
[php/doc-fr](https://github.com/php/doc-fr).

## Requesting official status

When the translation covers a useful part of the manual, write to
`phpdoc@lists.php.net` again with a link to the repository and ask for it to be
made official. The documentation team will look for:

- `chapters/`, `faq/`, `features/`, `language/` and `install/` translated and
  up to date with doc-en;
- `translation.xml`, `language-defs.ent` and `language-snippets.ent` present
  and filled in;
- a local build that completes without errors;
- a commit history that shows the translation is being worked on, ideally by
  more than one person;
- at least two people who will maintain the translation after it is published.

Once the team agrees, the following happens:

- the repository is transferred to `php/doc-{LANG}` and a GitHub team with
  commit access is created for the maintainers;
- the `doc-{LANG}@lists.php.net` mailing list is created;
- the translation is added to the status tools, so its progress shows up at
  <https://doc.php.net/revcheck.php>;
- the manual is added to the automatic build. It is rebuilt every two hours
  (see [How the released versions are built](public-builds.md)) and appears at
  `https://www.php.net/manual/{LANG}/` once the first build succeeds.

## After going live

The English manual keeps changing, so a published translation needs ongoing
attention:

- use the "Outdated files" tool at <https://doc.php.net/revcheck.php> to find
  files whose `EN-Revision` no longer matches doc-en;
- add every new contributor to `translation.xml`, and set `vcs="yes"` once
  they have commit access;
- ask on `phpdoc@lists.php.net` to add new maintainers to the GitHub team;
- use `doc-{LANG}@lists.php.net` for decisions about terminology and style, so
  that they are recorded for future contributors.

## For the documentation team: activating a language

This section is for members of the documentation team. It lists the changes
needed to turn a community repository into an official translation. Do them in
this order; each step depends on the previous one.

| Step | Where | What to do |
|------|-------|------------|
| Transfer the repository | GitHub | Transfer the repository to `php/doc-{LANG}`. Create the `doc-{LANG}` team in the `php` organization and give it write access, with the maintainers as members. |
| Mailing list | `systems@php.net` | Ask for `doc-{LANG}@lists.php.net` to be created, with the maintainers as initial subscribers. |
| Status tools | [`doc-base/languages.php`](https://github.com/php/doc-base/blob/master/languages.php) | Add a `lang()` line for the language. Set the `revcheck` flag to `true` so the status database is generated. Set the `manual` flag to `true` only when the manual should be built and published. |
| doc.php.net | [`web-doc/include/lib_proj_lang.inc.php`](https://github.com/php/web-doc/blob/master/include/lib_proj_lang.inc.php) | Add the language code and name to `$LANGUAGES`, so the language appears in the sidebar and `revcheck.php?lang={LANG}` works. |
| PhD strings | [`phd/phpdotnet/phd/data/langs/`](https://github.com/php/phd/tree/master/phpdotnet/phd/data/langs) | Add `{LANG}.ini` with the translated interface strings ("Table of Contents", "Note", "Warning", ...), based on `en.ini`. The translators should provide these. |
| PhD CHM (optional) | `phd/phpdotnet/phd/Package/PHP/CHM.php` and `Package/PEAR/CHM.php` | Add an entry with the Windows language code and charset if a CHM build is wanted. |
| php.net | [`web-php/src/I18n/Languages.php`](https://github.com/php/web-php/blob/master/src/I18n/Languages.php) and the deprecated `include/languages.inc` | Add the language to `LANGUAGES`. Add it to `ACTIVE_ONLINE_LANGUAGES` when the manual goes live; until then list it under `INACTIVE_ONLINE_LANGUAGES`. |
| doc-en README | [`doc-en/README.md`](https://github.com/php/doc-en/blob/master/README.md) | Add the repository to the "Translations" list. |
| Verify | <https://doc.php.net/logs/>, <https://doc.php.net/revcheck.php>, `https://www.php.net/manual/{LANG}/` | Check that the build log is clean, the status page renders for the language, and the manual loads. |

## Reply template

A short reply that can be sent to someone who asks on the mailing list how to
add a new language:

```
Hi {NAME},

Thank you for your interest in translating the PHP Manual into {LANGUAGE}.

We have written down the whole process, from starting a community repository
to becoming an official translation:

    https://doc.php.net/guide/new-language.md

In short:

 - Check the mailing list archive for others working on {LANGUAGE} and team up
   with them if you can; a translation needs more than one person to stay alive.
 - Start a doc-{LANG} repository under your own GitHub account, with only the
   files you have translated, each with an EN-Revision header.
 - Begin with chapters/, faq/, features/, language/ and install/, then move on
   to the reference/ section.
 - When those are done, write to this list again and we will move the
   repository into the php organization and add it to the manual build.

Could you let us know your GitHub username, the language code you intend to
use, and whether anyone else is working with you?

Feel free to ask questions here on the list. There is also a documentation
channel on the PHP Community Foundation Discord at https://phpc.chat.

Regards,
{YOUR NAME}
```
