#!/bin/sh
for file in `find en/functions -name "*.xml"` `find en/pear -name "*.xml"`
do
  echo $file
  cat $file | sed  -e"s/&/&amp;/g" | sabcmd dbk4_upgrade.xsl | sed -e's/&amp;/\&/g' > tmp
  mv $file $file.bak
  mv tmp $file
done
