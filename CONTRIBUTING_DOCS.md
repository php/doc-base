# Contributing to the PHP Documentation

You can easily contribute to the PHP documentation by either [reporting a bug](#report-a-bug)
or by [submitting a pull request](#submit-a-pull-request).
As the repositories are all hosted on GitHub,
you will need a GitHub account to do either of these.

## Report a Bug

If you have found a bug in the PHP documentation,
you can file a bug report by clicking the "Report a Bug" link
in the "Improve This Page" section on the bottom of the documentation page
you have found the bug on.

## Submit a Pull Request

There are two ways to contribute make changes to the documentation:
 - you can make [minor changes](#minor-changes) by directly editing files
 and submitting pull requests on GitHub
 - make [more complex changes](#more-complex-changes--building-the-php-documentation)
 and/or build the documentation locally on your computer

### Minor changes

To make trivial changes (typos, shorter wording changes or adding/removing short segments)
all one needs is a web-browser and a GitHub account and the followings:

 - click the "Submit a Pull Request" link in the "Improve This Page" section
  on the bottom of the documentation page
 - login to GitHub (if you are not logged in already)
 - fork the repository (if you have not forked it already)
 - make changes
 - click the "Commit changes" button
 - add a commit message (short description of the change) into the "Commit message" textbox
 - write a longer description in the "Extended description" textarea, if needed
 - click the "Propose changes" button
 - (on the new page base repo: php/doc-en <- head repo: your_user/doc-en),
  review change (removals in red, additions in green) and click "Create pull request" button

The repository will run a few basic checks on the changes
(e.g. whether the XML markup is valid, whether trailing whitespaces are stripped, etc.)
and your PR is ready to be discussed with and merged by the maintainers.

### More Complex Changes / Building the PHP documentation

To build and view the documentation after making more extensive changes
(e.g. adding entire sections or files), you will need to
[setup some repositories](#setting-up-a-development-environment)
(doc-base, phd and possibly web-php)
in addition to the language repository you want to
[make the changes](#making-and-submitting-changes) to.

If you'd like to know more about what each repository is
and/or how PHP's documentation is built please refer to
this [overview](https://github.com/php/doc-base/OVERVIEW.md).

#### Requirements

Following this guide to make changes to and build the documentation has the following dependencies:
 - PHP 8.0+ with the DOM, libXML2, XMLReader and SQLite3 extensions
 - Git
 - an IDE or a text editor

#### Setting up a development environment

 - clone the doc-base repository
 ```git clone https://github.com/php/doc-base```

 - clone the phd repository
 ```git clone https://github.com/php/phd```

 - clone the doc-* (language) repository
  To clone the English documentation:
 ```git clone https://github.com/php/doc-en en```
  Currently, the following languages/repositories are available:
   - [English](https://github.com/php/doc-en)
   - [German](https://github.com/php/doc-de)
   - [Spanish](https://github.com/php/doc-es)
   - [French](https://github.com/php/doc-fr)
   - [Italian](https://github.com/php/doc-it)
   - [Japanese](https://github.com/php/doc-ja)
   - [Brazilian Portugues](https://github.com/php/doc-pt_br)
   - [Russian](https://github.com/php/doc-ru)
   - [Turkish](https://github.com/php/doc-tr)
   - [Chinese(Simplified)](https://github.com/php/doc-zh)

If you would like to setup a local mirror of the documentation
or you would just like to see how your changes will look like online:
 - clone the web-php repository (if needed)
 ```git clone https://github.com/php/web-php```

 - create a symlink to the documentation source files
 ```$ cd web-php/manual```
 ```$ rm -rf en```
 ```$ ln -s ../../output/php-web en```
 where `../../output/php-web` is the directory the PhD generated .php files are saved at,
 relative to the `web-php/manual` directory.

 On Windows:
 ```$ cd \your\path\to\web-php\manual\```
 ```$ rmdir /S en```
 ```$ mklink /D en \your\path\to\output\web-php```
 where `\your\path\to\output\web-php` is the directory the PhD generated .php files are saved at,
 relative to the `\your\path\to\web-php\manual\` directory.

 You can view the documentation by using any web server
 but one of the simplest ways is to use PHP's built-in web server:
 ```$ cd web-php```
 ```$ php -S localhost:8080 .router.php```
 where `web-php` is the directory web-php has been cloned into.

 The manual is now available from http://localhost:8080/manual/en/ in your browser.

#### Making and submitting changes

 - make changes
 Make your changes keeping in mind the [style guidelines](http://doc.php.net/tutorial/style.php).

 - validate the documentation
  To validate the English documentation saved in the directory `en`, run
  ```php doc-base/configure.php```
  To validate any other documentation, run
  ```php doc-base/configure.php –with-lang=doc-lang```
  where `doc-lang` is the name of your language's repository (eg. `doc-de` for German).

 - render the documentation
 To render the documentation in html (can be viewed without a web server), run
 ```php phd/render.php --docbook doc-base/.manual.xml --package PHP --format xhtml```
 To render the documentation in php (needs a web server), run
 ```php phd/render.php --docbook doc-base/.manual.xml --package PHP --format php```

 - commit your changes
 - push changes and open a PR

Once you open a PR, the documentation repository will run a few basic checks on the changes
(e.g. whether the XML markup is valid, whether trailing whitespaces are stripped, etc.)
and your PR is ready to be discussed with and merged by the maintainers.

Please refer to the [tutorial page of the documentation website](http://doc.php.net/tutorial/)
and [doc-base's README](https://github.com/php/doc-base/README.md) for more details.
