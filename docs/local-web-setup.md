# Setting up a local web development enviroment

## Clone the php.net sources
```
$ git clone https://github.com/php/web-php.git
```

## Symlink (or move) the generated PHP documentation to your local php.net sources
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

## Run a webserver
We are going to use PHP's built-in web server. Please open another terminal instance for this task.

```
$ cd phpdoc/web-php
$ php -S localhost:8080 .router.php
```

## View the new site
Open [http://localhost:8080/manual/en/](http://localhost:8080/manual/en/) in your browser.
