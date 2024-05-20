# Contributing to the PHP Documentation

You can easily contribute to the PHP documentation
by either [reporting a bug](#report-a-bug)
or by fixing one by [submitting a pull request](#submit-a-pull-request).
As all the repositories are hosted on GitHub,
you will need a GitHub account to do either of these.

## Report a Bug

If you have found a bug on any of the PHP documentation pages,
you can file a bug report by doing the following:

 - click the "Report a Bug" link in the "Improve This Page" section
 on the bottom of the page
 - log into GitHub
 - add a short description of the bug in the title textbox
 - add all necessary details to the description textarea
 - click the "Submit new issue" button to file your bug report

## Submit a Pull Request

There are two ways to make changes to the documentation:
 - make [minor changes](#minor-changes) by editing files on GitHub
 - make [more complex changes](#more-complex-changes--building-the-php-documentation)
 and validate/build the documentation locally on your computer

## Minor changes

To make trivial changes (typos, shorter wording changes or adding/removing short segments)
all one needs is a web-browser and a GitHub account and doing the following:

 - click the "Submit a Pull Request" link in the "Improve This Page" section
  on the bottom of the documentation page
 - log into GitHub
 - fork the repository (if you have not forked it already)
 - make changes
 - click the "Commit changes" button
 - add a commit message (short description of the change) into the "Commit message" textbox
 - write a longer description in the "Extended description" textarea, if needed
 - click the "Propose changes" button
 - review your changes and click "Create pull request" button

The repository will run a few basic checks on the changes
(e.g. whether the XML markup is valid, whether trailing whitespaces are stripped, etc.)
and your PR is ready to be discussed with and merged by the maintainers.

## More Complex Changes / Building the PHP documentation

To build and view the documentation after making more extensive changes
(e.g. adding entire sections or files), you will need to
[clone some of the tooling](#set-up-a-development-environment)
(doc-base, phd and possibly web-php)
in addition to the language repository you want to
[make the changes](#make-changes-to-the-documentation) to.
You need to [validate the changes](#validate-the-documentation)
and you can (but do not have to) [render the documentation](#render-the-documentation)
before you [open a PR](#commit-changes-and-open-a-pr).

If you'd like to know more about what each repository is
and/or how PHP's documentation is built please refer to
this [overview](https://github.com/php/doc-base/OVERVIEW.md).

### Requirements

Following this guide to make changes to and build the documentation has the following dependencies:
 - PHP 8.0+ with the DOM, libXML2, XMLReader and SQLite3 extensions
 - Git
 - an IDE or a text editor

### Set up a development environment

#### Basic setup
To start working on the English documentation, you need to fork it on Github
and clone it, doc-base (for validating the XML files) and PhD (for rendering the files).

  ```shell
  git clone https://github.com/<your_github_username>/<your_fork> en
  git clone https://github.com/php/doc-base
  git clone https://github.com/php/phd
  cd en
  git remote add upstream https://github.com/php/doc-en
  ```
where `<your_github_username>` and `<your_fork>` needs to be replaced by
your GitHub username and the name of your for of the English documentation respectively.
Please note that the English documentation has to be cloned into the `en` directory.

To clone any of the translations, replace `doc-en en` at the end of the first line
with the translation you would like to clone
(e.g. `doc-pt_br` for the Brazilian documentation).
As an example, the following shows how to set up the Brazilian documentation:

  ```shell
  git clone https://github.com/<your_github_username>/doc-pt_br
  git clone https://github.com/php/doc-base
  git clone https://github.com/php/phd
  cd doc-pt_br
  git remote add upstream https://github.com/php/doc-pt_br
  ```
where `<your_github_username>` needs to be replaced by your GitHub username.

<details>
  <summary>List of languages/repositories</summary>

  - [Brazilian Portugues](https://github.com/php/doc-pt_br) (doc-pt_br)
  - [Chinese(Simplified)](https://github.com/php/doc-zh) (doc-zh)
  - [English](https://github.com/php/doc-en) (doc-en)
  - [French](https://github.com/php/doc-fr) (doc-fr)
  - [German](https://github.com/php/doc-de) (doc-de)
  - [Italian](https://github.com/php/doc-it) (doc-it)
  - [Japanese](https://github.com/php/doc-ja) (doc-ja)
  - [Polish](https://github.com/php/doc-pl) (doc-pl)
  - [Romanian](https://github.com/php/doc-ro) (doc-ro)
  - [Russian](https://github.com/php/doc-ru) (doc-ru)
  - [Spanish](https://github.com/php/doc-es) (doc-es)
  - [Turkish](https://github.com/php/doc-tr) (doc-tr)
  - [Ukrainian](https://github.com/php/doc-uk) (doc-uk)
</details>

#### Setting up a local mirror

If you would like to setup a local mirror of the documentation
or you would just like to see how your changes will look like online:

  ```shell
  git clone https://github.com/php/web-php
  cd web-php/manual
  rm -rf en
  ln -s ../../output/php-web en
  ```

  where `../../output/php-web` is the directory the PhD generated .php files are saved at,
  relative to the `web-php/manual` directory.

On Windows:

  ```shell
  git clone https://github.com/php/web-php
  cd \your\path\to\web-php\manual\
  rmdir /S en
  mklink /D en \your\path\to\output\web-php
  ```

  where `\your\path\to\output\web-php` is the directory the PhD generated .php files are saved at,
  relative to the `\your\path\to\web-php\manual\` directory.

#### Using your local mirror
 You can view the documentation by using any web server
 but one of the simplest ways is to use PHP's built-in web server:

 ```shell
 cd web-php
 php -S localhost:8080 .router.php
 ```

 where `web-php` is the directory web-php has been cloned into.

 The manual is now available from http://localhost:8080/manual/en/ in your browser.

### Make changes to the documentation

Make your changes keeping in mind the [style guidelines](http://doc.php.net/tutorial/style.php).

### Validate the documentation

To validate the English documentation (located in the directory `en`), run
```shell
php doc-base/configure.php
```

To validate any other documentation, run
```shell
php doc-base/configure.php --with-lang=doc-lang
```
where `doc-lang` is the name of your language's repository
and the corresponding directory (eg. `doc-de` for German).

*Please note that all validation errors have to be corrected
before opening a PR.*

### Render the documentation

To render the documentation in xhtml format (can be viewed without a web server), run
```shell
php phd/render.php --docbook doc-base/.manual.xml --package PHP --format xhtml
```

To render the documentation in php format (needs a web server and web-php), run
```shell
php phd/render.php --docbook doc-base/.manual.xml --package PHP --format php
```

### Commit changes and open a PR
 - commit your changes
 - push to your fork
 - open a PR in the appropriate language repository

Once you open a PR, the documentation repository will run a few basic checks on the changes
(e.g. whether the XML markup is valid, whether trailing whitespaces are stripped, etc.)
and your PR is ready to be discussed with and merged by the maintainers.

## Additional information

For additional information on contributing to the documentation refer to:

 - the [Adding new documentation](http://doc.php.net/tutorial/editing.php)
 section of the "Editing manual sources" page
 - the [Files structure](http://doc.php.net/tutorial/structure.php)
 section of the "Manual sources structure" page
 - the [Translating documentation](http://doc.php.net/tutorial/translating.php) page
 - the [Style guidelines](http://doc.php.net/tutorial/style.php) page
 - the [FAQ](http://doc.php.net/tutorial/faq.php) page
 - the [Why we care about whitespace](http://doc.php.net/tutorial/whitespace.php) page
 - doc-base's [README](https://github.com/php/doc-base/blob/master/README.md)
