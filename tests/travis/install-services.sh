#!/bin/bash
set -ex
BASE_PATH=$(pwd)
E_UNREACHABLE=86

if [ "$FOURSTORE" != "" ] || [ "$VIRTUOSO" != "" ] || [ "$SESAME" != "" ] || [[ "$FUSEKI" == "2."* ]]
then
	sudo apt-get update -qq
fi

# Version 1.1.0 is available and testable on Travis/SMW
if [ "$FUSEKI" != "" ]
then
	# Archive
	# http://archive.apache.org/dist/jena/binaries/jena-fuseki-$FUSEKI-distribution.tar.gz
	# http://www.eu.apache.org/dist/jena/binaries/jena-fuseki-$FUSEKI-distribution.tar.gz

	# Avoid ERROR 503: Service Unavailable
	# wget http://archive.apache.org/dist/jena/binaries/jena-fuseki-$FUSEKI-distribution.tar.gz

	if [[ "$FUSEKI" == "2."* ]]
	then

		# Fuseki requires Java8 for Fuseki2 v2.3.0 onwards
		#sudo apt-get install oracle-java8-installer

		#export JAVA_HOME="/usr/lib/jvm/java-8-oracle";
		#export PATH="$PATH:/usr/lib/jvm/java-8-oracle/bin";
		#export java_path="/usr/lib/jvm/java-8-oracle/jre/bin/java";

		wget https://github.com/mwjames/travis-support/raw/master/fuseki/$FUSEKI/apache-jena-fuseki-$FUSEKI.tar.gz

		# option z caused "gzip: stdin: not in gzip format"
		tar -xf apache-jena-fuseki-$FUSEKI.tar.gz
		mv apache-jena-fuseki-$FUSEKI fuseki
	else
		wget https://github.com/mwjames/travis-support/raw/master/fuseki/$FUSEKI/jena-fuseki-$FUSEKI-distribution.tar.gz

		tar -zxf jena-fuseki-$FUSEKI-distribution.tar.gz
		mv jena-fuseki-$FUSEKI fuseki
	fi

	cd fuseki

	## Start fuseki in-memory as background
	bash fuseki-server --update --mem /db &>/dev/null &
fi

if [ "$SESAME" != "" ]
then
	TOMCAT_VERSION=tomcat8
	sudo java -version

	sudo apt-get install $TOMCAT_VERSION

	CATALINA_BASE=/var/lib/$TOMCAT_VERSION
	CATALINA_HOME=/usr/share/$TOMCAT_VERSION

	sudo chown $USER -R $CATALINA_BASE/
	sudo chmod g+rw -R $CATALINA_BASE/

	## RDF4J SESAME 3
	wget "https://www.eclipse.org/downloads/download.php?file=/rdf4j/eclipse-rdf4j-$SESAME-sdk.zip&r=1" -O eclipse-rdf4j-$SESAME-sdk.zip

	unzip -q eclipse-rdf4j-$SESAME-sdk.zip
	sudo cp eclipse-rdf4j-$SESAME/war/*.war $CATALINA_BASE/webapps/

	sudo mkdir -p $CATALINA_BASE/.RDF4J
	sudo chown -R $TOMCAT_VERSION:$TOMCAT_VERSION $CATALINA_BASE

	sudo service $TOMCAT_VERSION restart
	ps -ef | grep tomcat

	sleep 5

	if curl --output /dev/null --silent --head --fail "http://localhost:8080/rdf4j-server"

	then
		echo "RDF4J service url is reachable"
	else
		echo "RDF4J service url is not reachable"
		sudo cat $CATALINA_BASE/logs/*.log &
		sudo cat $CATALINA_BASE/logs/catalina.out &
		exit $E_UNREACHABLE
	fi

	cat $BASE_PATH/tests/travis/openrdf-sesame-memory-repository.txt | ./eclipse-rdf4j-$SESAME/bin/console.sh -e
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
if [[ "$VIRTUOSO" == "6."* ]]
then
	sudo apt-get install -qq virtuoso-opensource
	echo "RUN=yes" | sudo tee -a /etc/default/virtuoso-opensource-$VIRTUOSO
	sudo service virtuoso-opensource-$VIRTUOSO start

	isql-vt 1111 dba dba $BASE_PATH/tests/travis/virtuoso-sparql-permission.sql
fi

# Version 7 is not available as deb package so we have to build it from scratch
if [[ "$VIRTUOSO" == "7."* ]]
then
	sudo apt-get install libssl-dev -q
	sudo apt-get install autoconf automake bison flex gawk gperf libtool -q

	#git clone git://github.com/openlink/virtuoso-opensource.git
	#cd virtuoso-opensource
	#git pull origin stable/7
	wget --no-check-certificate -q https://github.com/openlink/virtuoso-opensource/archive/v$VIRTUOSO.zip -O virtuoso-opensource.zip

	unzip -q virtuoso-opensource.zip
	mv virtuoso-opensource-$VIRTUOSO virtuoso-opensource

	cd virtuoso-opensource
	./autogen.sh

	# --disable-all-vads: This parameter disables building all the VAD packages (tutorials, demos, etc.).
	# --with-readline: This parameter is used so that the system Readline library is used
	# --program-transform-name: Both Virtuoso and unixODBC install a program named isql. Use this parameter to rename virtuosos program to isql-v

	./configure --program-transform-name="s/isql/isql-v/" --with-readline --disable-all-vads |& tee #configure.log

	# Only output error and warnings
	make > /dev/null

	# Build tree to start the automated test suite
	# make check

	sudo make install

	## For Virtuoso
	#export PATH=$PATH:/usr/local/virtuoso-opensource/bin

	sudo /usr/local/virtuoso-opensource/bin/virtuoso-t -f -c /usr/local/virtuoso-opensource/var/lib/virtuoso/db/virtuoso.ini &
	#sudo /usr/local/virtuoso-opensource/bin/virtuoso-t -f &

	sleep 10

	sudo /usr/local/virtuoso-opensource/bin/isql-v 1111 dba dba $BASE_PATH/tests/travis/virtuoso-sparql-permission.sql
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
	# Unreliable sourceforge.net download
	# wget http://downloads.sourceforge.net/project/bigdata/bigdata/$BLAZEGRAPH/bigdata-bundled.jar
	wget https://github.com/mwjames/travis-support/raw/master/blazegraph/$BLAZEGRAPH/bigdata-bundled.jar

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

if [ "$ES" != "" ]
then

	# Configure a specific version of Elasticsearch
	# See: https://docs.travis-ci.com/user/database-setup/#Installing-ElasticSearch-on-trusty-container-based-infrastructure

	if [[ "$ES" == "5."* ]]
	then
		# Travis Bionic comes with preinstalled openjdk11
		sudo java -version

		wget https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-${ES}.tar.gz

		tar -xzf elasticsearch-${ES}.tar.gz
	fi

	./elasticsearch-${ES}/bin/elasticsearch &>/dev/null &

	wget -q --waitretry=1 --retry-connrefused -T 10 -O - http://127.0.0.1:9200
fi
