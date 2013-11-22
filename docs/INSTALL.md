# SMW installation

These are the installation and configuration instructions for [Semantic MediaWiki](../README.md).

This is a brief version of the installation instructions, containing only the core steps. More
verbose installation instructions with additional explanation and upgrading instructions can be
found [on the SMW wiki](https://semantic-mediawiki.org/wiki/Help:Installation).

## Versions

<table>
	<tr>
		<th></th>
		<th>Status</th>
		<th>Release date</th>
		<th>PHP</th>
		<th>MediaWiki</th>
		<th>Git branch</th>
	</tr>
	<tr>
		<th><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/RELEASE-NOTES.md">SMW 1.9.x</a></th>
		<td>Development version</td>
		<td>Estimate: December 2013</td>
		<td>5.3.2 - 5.5.x</td>
		<td>1.19 - 1.23</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master">master</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.8.0">SMW 1.8.x</a></th>
		<td>Stable release</td>
		<td>2012-12-02</td>
		<td>5.2.0 - 5.5.x</td>
		<td>1.17 - 1.22</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.8.x">1.8.x</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.7.1">SMW 1.7.1</a></th>
		<td>Legacy release</td>
		<td>2012-03-05</td>
		<td>5.2.0 - 5.4.x</td>
		<td>1.16 - 1.19</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.7.1">1.7.1</a></td>
	</tr>
</table>

The PHP and MediaWiki version ranges listed are those in which SMW is known to work. It might also
work with more recent versions of PHP and MediaWiki, though this is not guaranteed.

### Database support

<table>
	<tr>
		<th></th>
		<th>MySQL</th>
		<th>SQLite</th>
		<th>PostgreSQL</th>
	</tr>
	<tr>
		<th>SMW 1.9.x</th>
		<td>Full support</td>
		<td>Full support</td>
		<td>Experimental</td>
	</tr>
	<tr>
		<th>SMW 1.8.x</th>
		<td>Full support</td>
		<td>Full support</td>
		<td>Experimental</td>
	</tr>
	<tr>
		<th>SMW 1.7.1</th>
		<td>Full support</td>
		<td>Experimental</td>
		<td>None</td>
	</tr>
</table>

## Download and installation

### With Composer

The recommended way to install Semantic MediaWiki is with [Composer](http://getcomposer.org), using
the [MediaWiki extension installation with Composer](https://www.mediawiki.org/wiki/Composer) support.

##### Step 1

If you have MediaWiki 1.22 or later, simply go to the root directory of your MediaWiki installation,
and skip ahead to step 2. When using an older version of MediaWiki, follow these instructions: 

Install the [Extension Installer](https://github.com/JeroenDeDauw/ExtensionInstaller/blob/master/README.md).
(Click this link for instructions).

Once you are done installing the Extension Installer, go to its directory.

    cd extensions/ExtensionInstaller

##### Step 2

To install, run the following commands

    wget http://getcomposer.org/composer.phar
    php composer.phar require mediawiki/semantic-mediawiki 1.9@dev

##### Step 3

Run the MediaWiki update script. The location of this script is maintenance/update.php. It can be run as follows:

    php maintenance/update.php

##### Verify installation success

As final step, you can verify SMW got installed by looking at the Special:Version page on your wiki.

### Using a tarball

As alternative to the first two installation steps, you can obtain the SMW code by getting one of the release tarballs.
These tarballs include all dependencies of SMW. This open exists mainly for those that have no command line access.
If you do, using the Composer approach is preferred.

(This section will be updated with details and links before the SMW 1.9 release)

### Manual installation

You can also obtain the SMW code and the code of all its dependencies yourself, and load them all.
This is highly discouraged as it is labour intensive and is quite brittle.

You can find a list of the dependencies in the "requires" section of the [composer.json file]
(../composer.json). These packages are also linked on the [SMW Packagist page]
(https://packagist.org/packages/mediawiki/semantic-mediawiki). Note that this process is recursive
and needs to be applied on the dependencies as well, since they can have further dependencies.

You can get the SMW code itself:

* Via git: git clone https://github.com/SemanticMediaWiki/SemanticMediaWiki.git
* As Tarball: https://github.com/SemanticMediaWiki/SemanticMediaWiki/releases

The only remaining step is to include SemanticMediaWiki in your LocalSettings.php file:

```php
require_once( "$IP/extensions/SemanticMediaWiki/SemanticMediaWiki.php" );
```

## More instructions

* [Verbose installation instructions](https://semantic-mediawiki.org/wiki/Help:Installation)
* [Upgrading instructions](https://semantic-mediawiki.org/wiki/Help:Installation#Upgrading_existing_installations)
* [Configuration instructions](https://semantic-mediawiki.org/wiki/Help:Configuration)
* [Administrator manual](https://semantic-mediawiki.org/wiki/Help:Administrator_manual)
