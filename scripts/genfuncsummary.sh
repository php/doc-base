#!/bin/sh 
#
# +----------------------------------------------------------------------+
# | PHP Version 4                                                        |
# +----------------------------------------------------------------------+
# | Copyright (c) 1997-2002 The PHP Group                                |
# +----------------------------------------------------------------------+
# | This source file is subject to version 2.02 of the PHP licience,     |
# | that is bundled with this package in the file LICENCE and is         |
# | avalible through the world wide web at                               |
# | http://www.php.net/license/2_02.txt.                                 |
# | If uou did not receive a copy of the PHP license and are unable to   |
# | obtain it through the world wide web, please send a note to          |
# | license@php.net so we can mail you a copy immediately                |
# +----------------------------------------------------------------------+
# | Authors:    Gabor Hoitsy <goba@php.net>                              |
# +----------------------------------------------------------------------+
#
# $Id: genfuncsummary.sh,v 1.5 2002-01-27 16:57:50 hholzgra Exp $

if test -f funcsummary.awk; then
  awkscript=funcsummary.awk
elif test -f scripts/funcsummary.awk; then
  awkscript=scripts/funcsummary.awk
else
  echo 1>&2 funcsummary.awk not found
	exit
fi
	

for i in `find $1 -name "*.[ch]" -print -o -name "*.ec" -print | xargs egrep -li "{{{ proto" | sort` ; do
 echo $i | sed -e "s|$1|# php4|"
 awk -f $awkscript < $i | sort +1 | awk -F "---" '{ print $1; print $2; }' | sed 's/^[[:space:]]+//'
done
if test -f $1/language-scanner.lex # only in PHP3
then
  awk -f funcsummary.awk < $1/language-scanner.lex | sort +1 | awk -F "---" '{ print $1; print $2; }'
fi
