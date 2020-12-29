@ECHO OFF

CALL "php.exe" -d memory_limit=-1 "C:\phpdoc\doc-base\scripts\build-chms.php" %*
CALL "C:\Program Files\Windows Defender\MpCmdRun.exe" -Scan -ScanType 3 -File "C:\Dropbox\Dropbox\Public\chm" > C:\Log.txt
if "%ERRORLEVEL%"  == "0" (
  echo "no chm malware detected" >> C:\Log.txt
  CALL aws s3 sync "C:\Dropbox\Dropbox\Public\chm" s3://phpmanualchm/
)
CALL "C:\Windows\System32\shutdown.exe" -s -t 120 -f -c "force shutdown after building chm"
