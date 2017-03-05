#!/bin/bash

name=`basename -s .odt $1`
src=./src/w2m

rm $name.xml

php -f $src/algone-code/odt2tei/Odt.php $1

echo $name.xml

# cat $name.xml | xsltproc tei2markdown.xslt -

cat $name.xml | java -jar saxon9he.jar -s:- -xsl:$src/tei2markdown.xsl > $name.md
