# Starting a new translation

Thank you for wanting to translate the PHP Manual! This chapter explains how
to start a translation into a language that is not yet listed at
<https://www.php.net/docs.php>, and what is needed before it is published
on php.net.

A translation is a long-term commitment. The manual has more than ten
thousand files and changes every day, so a translation is only useful as long
as people keep it up to date. That is why the steps below focus on finding a
team and building up steadily, rather than on translating as much as possible
at once.

## 1. Check whether the language already exists

Look for your language in
[`languages.php`](https://github.com/php/doc-base/blob/master/languages.php)
and in the [open issues](https://github.com/php/doc-base/issues) of doc-base.
If a repository or an issue for your language already exists, join that effort
instead of starting a new one.

## 2. Open an issue and announce it

Open an issue in [php/doc-base](https://github.com/php/doc-base/issues/new) titled `New translation: {Language} ({LANG})`,
where `{LANG}` is the two-letter [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes) code. This issue is where
others who want to help can find you, and where you post your progress.

Then write to `phpdoc@lists.php.net` with a link to the issue, so the
documentation team and other translators see it. You need to
[subscribe to the list](https://news-web.php.net/php.doc) before you can post.

## 3. Find a team

You need **at least three translators** before a translation can be
published. Translating alone is a quick way to burn out, and a team can review
each other's work and keep going when someone has less time. Use the issue and
the mailing list to find others, and ask people in your local PHP community.

## 4. Set up a repository

Create a repository named `doc-{LANG}` under your own GitHub account. It
follows the structure of [php/doc-en](https://github.com/php/doc-en) (see
[Documentation structure](structure.md)), with a few rules:

- Only add files that are translated. Missing files are taken from the English
  manual automatically.
- Every translated file starts with an `EN-Revision` comment, explained in
  [Translating documentation](translating.md).
- Add `language-defs.ent`, `language-snippets.ent` and a `translation.xml` that
  lists the team, as in [doc-it](https://github.com/php/doc-it).

Build the manual locally with your language to catch errors early, as
described in [Setting up a local build environment](local-setup.md).

## 5. Translate the essentials first

Start with these parts:

- `language/`
- `reference/array/` and `reference/strings/`
- `chapters/`, `faq/`, `features/` and `install/`

After that, continue with other commonly used extensions.

## 6. Work together

- Use pull requests, and let a team member review each one before it is merged.
- Prefer small, regular commits over large ones.

### No LLM translations

Do not submit translations made with an LLM or another machine translation
tool. Readers can already do this themselves in their browser, so it adds
nothing to the manual. Fixing generated text also takes more effort than
translating from scratch, and its mistakes are easy to miss. Such
translations will not be accepted.

## 7. Becoming an official translation

When the essentials are translated and up to date, your team has at least
three active translators, and you have worked steadily for at least three
months, post an update in your issue and ask for the translation to be made
official. The documentation team will review it and, once agreed, move the
repository to the `php` organization, create a `doc-{LANG}@lists.php.net`
mailing list and publish the manual on php.net.
