#! /bin/bash

# Utility for creating SMW tarballs
# By Jeroen De Dauw < jeroendedauw@gmail.com >
# Released under the GNU GPL v2+

# Parameters:
# $1: version fed to composer, defaults to dev-master
# $2: version used in the tarball name, defaults to $1

COMPOSER_VERSION="$1"
VERSION="$2"

if [ "$COMPOSER_VERSION" == "" ]; then
	COMPOSER_VERSION="dev-master"
fi

if [ "$VERSION" == "" ]; then
	VERSION=$COMPOSER_VERSION
fi

BUILD_DIR="build-$VERSION"

rm -rf $BUILD_DIR
mkdir $BUILD_DIR
cd $BUILD_DIR

composer create-project mediawiki/semantic-media-wiki SemanticMediaWiki $COMPOSER_VERSION -s dev --prefer-dist --no-dev

NAME="Semantic MediaWiki $VERSION (+dependencies)"
DIR="SemanticMediaWiki"

zip -r "$NAME.zip" $DIR
7z a "$NAME.7z" $DIR
tar -c $DIR | gzip > "$NAME.tgz"

cd ..
set -x
ls -lap $BUILD_DIR