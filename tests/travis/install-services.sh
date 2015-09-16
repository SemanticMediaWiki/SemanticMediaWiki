#!/bin/bash
set -ex
BASE_PATH=$(pwd)
E_UNREACHABLE=86

if [ "$FOURSTORE" != "" ] || [ "$VIRTUOSO" != "" ] || [ "$SESAME" != "" ]
then
	sudo apt-get update -qq
fi

# Version 1.1.0 is available and testable on Travis/SMW
if [ "$FUSEKI" != "" ]
then
	# Archive
	# http://archive.apache.org/dist/jena/binaries/jena-fuseki-$FUSEKI-distribution.tar.gz
	# http://www.eu.apache.org/dist/jena/binaries/jena-fuseki-$FUSEKI-distribution.tar.gz

	wget http://archive.apache.org/dist/jena/binaries/jena-fuseki-$FUSEKI-distribution.tar.gz
	tar -zxf jena-fuseki-$FUSEKI-distribution.tar.gz
	mv jena-fuseki-$FUSEKI fuseki

	cd fuseki

	## Start fuseki in-memory as background
	bash fuseki-server --update --mem /db &>/dev/null &
fi

if [ "$SESAME" != "" ]
then
	sudo apt-get install tomcat6

	sudo chown $USER -R /var/lib/tomcat6/
	sudo chmod g+rw -R /var/lib/tomcat6/

	sudo mkdir -p /usr/share/tomcat6/.aduna
	sudo chown -R tomcat6:tomcat6 /usr/share/tomcat6

	# One method to get the war files
	# wget http://search.maven.org/remotecontent?filepath=org/openrdf/sesame/sesame-http-server/$SESAME/sesame-http-server-$SESAME.war -O openrdf-sesame.war
	# wget http://search.maven.org/remotecontent?filepath=org/openrdf/sesame/sesame-http-workbench/$SESAME/sesame-http-workbench-$SESAME.war -O openrdf-workbench.war
	# cp *.war /var/lib/tomcat6/webapps/

	# http://sourceforge.net/projects/sesame/
	wget http://downloads.sourceforge.net/project/sesame/Sesame%202/$SESAME/openrdf-sesame-$SESAME-sdk.zip

	# tar caused a lone zero block, using zip instead
	unzip -q openrdf-sesame-$SESAME-sdk.zip
	cp openrdf-sesame-$SESAME/war/*.war /var/lib/tomcat6/webapps/

	sudo service tomcat6 restart
	sleep 3

	if curl --output /dev/null --silent --head --fail "http://localhost:8080/openrdf-sesame"
	then
		echo "openrdf-sesame service url is reachable"
	else
		echo "openrdf-sesame service url is not reachable"
		exit $E_UNREACHABLE
	fi

	./openrdf-sesame-$SESAME/bin/console.sh < $BASE_PATH/tests/travis/openrdf-sesame-memory-repository.txt
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

	isql-vt 1111 dba dba $BASE_PATH/tests/travis/virtuoso-sparql-permission.sql
fi

#@see  http://wiki.blazegraph.com/wiki/index.php/NanoSparqlServer
if [ "$BLAZEGRAPH" != "" ]
then
	#sudo apt-get install tomcat6

	#sudo chown $USER -R /var/lib/tomcat6/
	#sudo chmod g+rw -R /var/lib/tomcat6/

	#sudo mkdir -p /usr/share/tomcat6/.aduna
	#sudo chown -R tomcat6:tomcat6 /usr/share/tomcat6

	# http://sourceforge.net/projects/bigdata/
	#wget http://downloads.sourceforge.net/project/bigdata/bigdata/$BLAZEGRAPH/bigdata.war

	#cp bigdata.war /var/lib/tomcat6/webapps/
	#export JAVA_OPTS="-server -Xmx2g -Dcom.bigdata.rdf.sail.webapp.ConfigParams.propertyFile="$BASE_PATH/tests/travis/blazegraph-store.properties

	#sudo service tomcat6 restart
	#sleep 3

	#Using the jar
	wget http://downloads.sourceforge.net/project/bigdata/bigdata/$BLAZEGRAPH/bigdata-bundled.jar

	java -server -Xmx4g -Dbigdata.propertyFile=$BASE_PATH/tests/travis/blazegraph-store.properties -jar bigdata-bundled.jar &>/dev/null &
	sleep 5

	if curl --output /dev/null --silent --head --fail "http://localhost:9999/bigdata"
	then
		echo "blazegraph service url is reachable"
	else
		echo "blazegraph service url is not reachable"
		exit $E_UNREACHABLE
	fi

fi