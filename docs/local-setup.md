# Setting up a documentation environment
This appendix describes how to check out, build and view the PHP documentation locally.

Viewing results as a php.net mirror isn't a simple process, but it can be done.
The following is one route, and it assumes:

- PHP 7.2+, with the following extensions enabled: DOM, libXML2, XMLReader, JSON and SQLite3
- Git version control system is available
- A basic level of shell/terminal usage, or know that shell commands follow a `$` below

If you're interested in simply setting up a local PHP mirror (and NOT build the documentation) then
please follow the php.net [mirroring guidelines](http://php.net/mirroring) and ignore this document.

## Checkout the php documentation from Git
**Assumptions**: A working directory `phpdoc` is created and will contain the necessary cloned repositories. This tutorial will reference the directories that repositories are cloned into. Adjust as needed, including paths, especially if you are on Windows. This tutorial will clone at least three repositories:
 * doc-en (into the `en` directory)
 * doc-base 
 * phd

If setting up a local php.net mirror is desired, the `web-php` repository must also be cloned. This is explained in the [Set up a local php.net mirror](#set-up-a-local-phpnet-mirror) section.

Note that `doc-en` is cloned into the `en` directory below.
```
$ mkdir phpdoc
$ cd phpdoc
$ git clone https://github.com/php/doc-en.git en
$ git clone https://github.com/php/doc-base.git
```

## Validate the PHP Documentation XML
```
$ php doc-base/configure.php
```
Running `configure.php` will check and validate the XML according to the Docbook specification. If you receive an error message: resolve the error message and try running the configure.php script again. If you see an ASCII cat, your XML is valid.

## Build the documentation
We use PhD to build the documentation. It takes the source that `configure.php` generates, and builds
and formats the PHP documentation. PhD offers several formats including HTML (multiple or single page),
PDF, Man, Epub, and others, including PHP which is what we use at php.net.

### Install PhD
```
$ git clone https://github.com/php/phd.git
$ php phd/render.php --help
```

### Use PhD to build the documentation
As previously stated, there are multiple formats PhD can build the docs, but the two most commonly used for checking changes are XHTML and PHP. Building the docs into PHP requires a web server to view the pages, whereas XHTML does not. For small changes, building docs into XHTML is likely preferable. An example of a small change is modifying one file.
```
$ php doc-base/configure.php
$ php phd/render.php --docbook doc-base/.manual.xml --package PHP --format xhtml
$ open output/php-chunked-xhtml/function.strlen.html
```

However, if you wish to view the docs in an environment that closely resembles php.net, or wish to host a local mirror, the docs should be built in PHP format.

```
$ php doc-base/configure.php
$ php phd/render.php --docbook doc-base/.manual.xml --package PHP --format php
```
During the rendering process, PhD creates an `output` directory and builds the PHP manual files into a `php-web` directory inside of `output`.

**Note**: This builds the php.net version of the documentation, but does not include the necessary files to run
php.net. In other words, files like the php.net headers and footers are not built by PhD and are instead stored in a
[separate git module (web-php)](https://github.com/php/web-php).

<a id="set-up-a-local-phpnet-mirror"></a>
## Set up a local php.net mirror
### Clone the php.net sources
```
$ git clone https://github.com/php/web-php.git
```

### Symlink (or move) the generated PHP documentation to your local php.net sources
```
$ cd web-php/manual
$ rm -rf en
$ ln -s ../../output/php-web en
```

Symlinking can also be done on Windows. Just make sure you run `cmd` *as Administrator*.

```
$ cd \your\path\to\web-php\manual\
$ rmdir /S en
$ mklink /D en \your\path\to\output\web-php
```

### Run a webserver
We are going to use PHP's built-in web server. Please open another terminal instance for this task.

```
$ cd phpdoc/web-php
$ php -S localhost:8080 .router.php
```

## View the new site
Open [http://localhost:8080/manual/en/](http://localhost:8080/manual/en/) in your browser.
