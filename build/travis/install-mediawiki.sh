#!/bin/bash
set -ex

cd ..

## WMF removed release tags from mediawiki github and there has been no
## indication (https://phabricator.wikimedia.org/T100409) whether this is going
## to be solved or a permanent state therefore we try to match tags ourselves
case "$MW" in
'1.23.5')
  MW='master@c9bd517b21'
  ;;
'1.24.1')
  MW='master@07680d5579'
  ;;
'1.22.12')
  MW='master@ac80015657'
  ;;
'1.19.20')
  MW='master@1c7800109b'
  ;;
esac

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
if [ "$MW" == "master" ]
then
  composer init && composer install
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
