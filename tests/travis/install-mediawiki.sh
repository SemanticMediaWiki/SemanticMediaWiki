#!/bin/bash
set -ex

cd ..

## Use sha (master@5cc1f1d) to download a particular commit to avoid breakages
## introduced by MediaWiki core
if [[ "$MW" == *@* ]]
then
  arrMw=(${MW//@/ })
  MW=${arrMw[0]}
  SOURCE=${arrMw[1]}
else
 MW=$MW
 SOURCE=$MW
fi

wget https://github.com/wikimedia/mediawiki/archive/$SOURCE.tar.gz -O $MW.tar.gz

tar -zxf $MW.tar.gz
mv mediawiki-* mw

cd mw

## MW 1.25 requires Psr\Logger
if [ -f composer.json ]
then
  # Hack to fix "... jetbrains/phpstorm-stubs/PhpStormStubsMap.php): failed to open stream: No such file or directory ..."
  # https://phabricator.wikimedia.org/T226766
  composer remove jetbrains/phpstorm-stubs --no-interaction

  composer install
fi

if [ "$DB" == "postgres" ]
then
  # See #458
  sudo /etc/init.d/postgresql stop

  # Travis@support: Try adding a sleep of a few seconds between starting PostgreSQL
  # and the first command that accesses PostgreSQL
  sleep 3

  sudo /etc/init.d/postgresql start
  sleep 3

  psql -c 'create database its_a_mw;' -U postgres
  php maintenance/install.php --dbtype $DB --dbuser postgres --dbname its_a_mw --pass nyan TravisWiki admin --scriptpath /TravisWiki
else
  mysql -e 'create database its_a_mw;'
  php maintenance/install.php --dbtype $DB --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin --scriptpath /TravisWiki
fi
