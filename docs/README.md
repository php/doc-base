# PHP Manual Contribution Guide

## Introduction

PHP is well-known for having excellent documentation. That documentation
is created by volunteers who collectively make changes every day, and
actively translated into many languages. This guide is designed for
people who work on the official PHP documentation.

The PHP Documentation uses the [DocBook 5.2](https://tdg.docbook.org/tdg/5.2/)
XML schema, with one change to allow the `<classsynopsis>` tag to support
more than one `<ooclass>`, `<ooexception>`, or `<oointerface>` as the
[DocBook 5.1](https://tdg.docbook.org/tdg/5.1/classsynopsis) schema does.

The manual is built from the documentation using a tool called
[PhD](http://doc.php.net/phd.php). The [local setup](local-setup.md)
chapter explains how to set up a local development environment.

## Glossary

This guide uses some terminology that you have to know. Don't worry, it's easy:

- **author** - person who contributes to the original English manual
- **translator** - person who translates the English manual into another
  language
- **{LANG}** - replace it with your two-letter country code, (e.g. when
  referring to a mailinglist, `doc-{LANG}@lists.php.net`). Note:
  Brazilian Portuguese differs from the rest and it's called *pt_br*
  for the Git repo and *pt-br* for the mailing list suffix.

## Table of Contents
- [Overview of the build system](overview.md)
- [Setting up a local build environment](local-setup.md)
- [Editing the PHP Manual](editing.md)
- [Documentation structure](structure.md)
- [Style guidelines](style.md)
- [Coding standard for examples](cs-for-examples.md)
- [Translating documentation](translating.md)
- [Joining the team](joining.md)

## Appendices
- [FAQ](faq.md)
- [Tools](tools.md)
- [Frequently used tags and entities](tags-and-entities.md)
- [Why whitespace is important in phpdoc](whitespace.md)
- [User Note Editing Guidelines](user-notes.md)
- [How the released versions are built](public-builds.md)

## Feedback

You can report problems or make contributions to this guide by using the
"Edit this page" or "Report a problem" links in the header of each page
at [the online version of this documentation](https://doc.php.net/tutorial/),
or through [the GitHub repository](https://www.github.com/php/doc-base/).