# Editing manual sources

Before making any changes to the manual - either the English version or a
translation, make sure you have read the [style guidelines](style.md)!

## Editing existing documentation
Simply open the files and edit them.

## Adding new documentation
When adding new functions or methods, there are a few options:

### Option A: Generating stub using `gen_stub.php`
This is the new preferred way to generate files for new extensions, classes, functions
or methods using [`get_stub.php`][gen_stub]. The script is found in the [php-src][php-src]
repository and uses the stub files to generate documentation (DocBook) files.

### Option B: Generating files using docgen
This is the old preferred way to generate files for new extensions, classes, functions
or methods using [`docgen`][docgen]. The script is found in the [doc-base][doc-base]
repository and uses reflection to generate documentation (DocBook) files.

### Option C: Copy skeleton files
This involves copying the skeleton files into the correct location:
```
cp doc-base/skeletons/method.xml classname/methodname.xml   #for new methods
cp doc-base/skeletons/function.xml functions/functionname.xml #for new functions
```

Note: *classname*, *methodname* and *functionname* are lowercased names of the
class, method, or function, respectively, not a literal file name.

Remember the extension folder [structure](structure.md) when copying those files.

## Translating documentation
The translation process is described in the [translating chapter](translating.md).

Changes in the English version are eventually picked up by the translators.
If a change doesn't affect translations (e.g. fixing a typo in English) then the
commit message should start with `[skip-revcheck]`.

## Validating your changes
Every time you make changes to documentation sources (both English or translation),
you have to validate your changes to ensure that the manual still builds without error.
The necessary [configure.php][configure.php] script is distributed with the
[doc-base][doc-base] repository, so you should already have it. All you have
to do to validate changes is run configure.php:
```
$ cd phpdoc
$ php configure.php --with-lang={LANG}
```
If your language is English you can omit the `with-lang` argument. When the above
outputs something like `"All good. Saving .manual.xml… done."` then you know it validates.
You can commit your changes now.

## Commit changes
If you have the appropriate access to the repository, you can commit your modified files.
Otherwise, create a Pull Request to have your changes reviewed by the team.

## Viewing changes online
The documentation used on the PHP.net website is rebuilt every few hours from
the latest source pushed to the documentation trees.

Read more details in [the appendix on public builds](public-builds.md).

[docgen]: https://github.com/php/doc-base/tree/master/scripts/docgen
[doc-base]: https://github.com/php/doc-base/
[gen_stub]: https://github.com/php/php-src/tree/master/build/gen_stub.php
[php-src]: https://github.com/php/php-src/
[configure.php]: https://github.com/php/doc-base/blob/master/configure.php
