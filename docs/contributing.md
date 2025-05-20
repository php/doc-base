# Contributing to the PHP Documentation

You can easily contribute to the PHP documentation
by either [reporting a bug](#report-a-bug)
or by fixing one by [submitting a pull request](#submit-a-pull-request).
As all the repositories are hosted on GitHub,
you will need a GitHub account to do either of these.

<a name="report-a-bug"></a>
## Report a Bug

If you have found a bug on any of the PHP documentation pages,
you can file a bug report by doing the following:

- click the "Report a Bug" link in the "Improve This Page" section
 on the bottom of the page
- log into GitHub
- add a short description of the bug in the title textbox
- add all necessary details to the description textarea
- click the "Submit new issue" button to file your bug report

<a name="submit-a-pull-request"></a>
## Submit a Pull Request

There are two ways to make changes to the documentation:
- make [minor changes](#minor-changes) by editing files on GitHub
- make [more complex changes](#more-complex-changes--building-the-php-documentation)
 and validate/build the documentation locally on your computer

<a name="minor-changes"></a>
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

<a name="more-complex-changes--building-the-php-documentation"></a>
## More Complex Changes / Building the PHP documentation

To build and view the documentation after making more extensive changes
(e.g. adding entire sections or files), you will need to
[set up a local build environment](local-setup.md)
in addition to the language repository you want to
[make the changes](#make-changes-to-the-documentation) to.
If the changes validate and look good, you can
[open a PR](#commit-changes-and-open-a-pr).

If you'd like to know more about what each repository is
and/or how PHP's documentation is built please refer to
the [overview](overview.md).

<a name="make-changes-to-the-documentation"></a>
### Make changes to the documentation

[Make your changes](editing.md) keeping in mind the [style guidelines](style.md).

<a name="commit-changes-and-open-a-pr"></a>
### Commit changes and open a PR

- commit your changes
- push to your fork
- open a PR in the appropriate language repository

Once you open a PR, the documentation repository will run a few basic
checks on the changes (e.g. whether the XML markup is valid, whether
trailing whitespaces are stripped, etc.)  and your PR is ready to be
discussed with and merged by the maintainers.
