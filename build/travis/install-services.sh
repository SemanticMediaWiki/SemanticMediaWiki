#!/bin/bash
set -ex
BASE_PATH=$(pwd)

if [ "$FOURSTORE" != "" ] || [ "$VIRTUOSO" != "" ]
then
	sudo apt-get update -qq
fi

# Version 1.0.2 is available and testable on Travis/SMW
if [ "$FUSEKI" != "" ]
then

	wget http://archive.apache.org/dist/jena/binaries/jena-fuseki-$FUSEKI-distribution.tar.gz
	tar -zxf jena-fuseki-$FUSEKI-distribution.tar.gz
	mv jena-fuseki-$FUSEKI fuseki

	cd fuseki

	## Start fuseki in-memory as background
	bash fuseki-server --update --mem /db &>/dev/null &
fi

# Version 1.1.4-1 is available but has a problem
# https://github.com/garlik/4store/issues/110
# 4STORE can not be used as variable name therefore FOURSTORE
if [ "$FOURSTORE" != "" ]
then

	sudo mkdir /var/lib/4store/
	sudo mkdir /var/lib/4store/db
	sudo chown $USER -R /var/lib/4store/
	sudo chmod g+rw -R /var/lib/4store/

	sudo apt-get install 4store=$FOURSTORE

	## Disabling the firewall
	sudo iptables -F

	4s-backend-setup db
	4s-backend db

	## Output the current process table
	ps auwwx | grep 4s-

	## -D only used to check the status of the 4store instance
	## 4s-httpd -D -p 8088 db

	4s-httpd -p 8088 db
fi

# Version 6.1 is available
if [ "$VIRTUOSO" != "" ]
then
	sudo apt-get install -qq virtuoso-opensource
	echo "RUN=yes" | sudo tee -a /etc/default/virtuoso-opensource-$VIRTUOSO
	sudo service virtuoso-opensource-$VIRTUOSO start

	isql-vt 1111 dba dba $BASE_PATH/build/travis/virtuoso-sparql-permission.sql
fi
