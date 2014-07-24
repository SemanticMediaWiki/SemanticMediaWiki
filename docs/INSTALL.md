# Installation guide (brief)

This is a brief installation and configuration guide for [Semantic MediaWiki](../README.md) that
only contains the core steps. More
verbose installation instructions with additional explanation and upgrading instructions can be
found [here](https://semantic-mediawiki.org/wiki/Help:Installation).

A list of supported PHP versions, MediaWiki versions and databases per SMW release can be found
in the [compatibility matrix](COMPATIBILITY.md).


## Download and installation

### Composer Installation

The recommended way to install Semantic MediaWiki is with [Composer](http://getcomposer.org) using
[MediaWiki 1.22 built-in support for Composer](https://www.mediawiki.org/wiki/Composer). MediaWiki
versions prior to 1.22 can use Composer via the
[Extension Installer](https://github.com/JeroenDeDauw/ExtensionInstaller/blob/master/README.md)
extension.

##### Step 1

If you have MediaWiki 1.22 or later, go to the root directory of your MediaWiki installation,
and go to step 2. You do not need to install any extensions to support composer.

For MediaWiki 1.21.x and earlier you need to install the
[Extension Installer](https://github.com/JeroenDeDauw/ExtensionInstaller/blob/master/README.md) extension.

Once you are done installing the Extension Installer extension, go to its directory so composer.phar
is installed in the right place.

    cd extensions/ExtensionInstaller

##### Step 2

If you have previously installed Composer skip to step 3.

To install Composer:

    wget http://getcomposer.org/composer.phar

##### Step 3
    
Now using Composer, install Semantic MediaWiki.

If you do not have a composer.json file yet, copy the composer-example.json file to composer.json. If you are using the Extension Installer extension, the file to copy will be named example.json, rather than composer-example.json. When this is done, run:
    
    php composer.phar require mediawiki/semantic-media-wiki:@dev

<sup>@dev</sup> refers to the latest development version while selecting an appropriate version is at your discretion.

##### Step 4

Run the MediaWiki [update script](https://www.mediawiki.org/wiki/Manual:Update.php). The location of this script is maintenance/update.php. It can be run as follows:

    php maintenance/update.php

##### Step 5

Add the following line to the end of your LocalSettings.php file.

    enableSemantics( 'example.org' );

##### Verify installation success

As final step, you can verify SMW got installed by looking at the "Special:Version" page on your wiki and verifying the
Semantic MediaWiki section is listed.

### Installation without shell access

As an alternative to installing via Composer, you can obtain the SMW code by getting one of the release tarballs.
These tarballs include all dependencies of SMW.

This option exists mainly for those that have no command line access. A drawback of this approach is that it makes
your setup incompatible with extensions that share dependencies with SMW. You are thus highly encouraged to use
the Composer approach if you have command line access.

##### Step 1

Download an SMW tarball and extract it into your extensions directory.

* [Download tarball of the latest SMW release](https://sourceforge.net/projects/semediawiki/files/latest/download)
* [List of SMW tarballs](https://sourceforge.net/projects/semediawiki/files/semediawiki/)

##### Step 2

Add the following lines to the end of your LocalSettings.php file.

    require_once "$IP/extensions/SemanticMediaWiki/SemanticMediaWiki.php";
    enableSemantics( 'example.org' );

###### Step 3

Log in as a user with administrator permission to your wiki and go to the page "Special:SMWAdmin": 

* Click on the "Initialise or upgrade tables" button in the "Database installation and upgrade" section to setup the database.
* Click on the "Start updating data" button in the "Data repair and upgrade" section to activate the [automatic data update](https://semantic-mediawiki.org/wiki/Repairing_SMW).

##### Verify installation success

As final step, you can verify SMW got installed by looking at the Special:Version page on your wiki and verifying the
Semantic MediaWiki section is listed.

## More instructions

* [Verbose installation instructions](https://semantic-mediawiki.org/wiki/Help:Installation)
* [Upgrading instructions](https://semantic-mediawiki.org/wiki/Help:Installation#Upgrading)
* [Configuration instructions](https://semantic-mediawiki.org/wiki/Help:Configuration)
* [Administrator manual](https://semantic-mediawiki.org/wiki/Help:Administrator_manual)
