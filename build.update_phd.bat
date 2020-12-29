cd C:\Software\phd
git pull
call "C:\Software\PHP7.2.6\pear.bat" uninstall doc.php.net/PhD doc.php.net/PhD_Generic doc.php.net/PhD_PHP
call "C:\Software\PHP7.2.6\pear.bat" install "C:\Software\phd\package.xml" "C:\Software\phd\package_generic.xml" "C:\Software\phd\package_php.xml"
