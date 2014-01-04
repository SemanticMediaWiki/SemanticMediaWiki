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
		<th>Git branch</th>
	</tr>
	<tr>
		<th><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/RELEASE-NOTES.md">SMW 1.9.0.x</a></th>
		<td>Development version</td>
		<td>-</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master">master</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.9.0">SMW 1.9.0</a></th>
		<td>Stable release</td>
		<td>2014-01-03</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.9">1.9.0</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.8.0">SMW 1.8.x</a></th>
		<td>Stable release</td>
		<td>2012-12-02</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.8.x">1.8.x</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.7.1">SMW 1.7.1</a></th>
		<td>Legacy release</td>
		<td>2012-03-05</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.7.1">1.7.1</a></td>
	</tr>
</table>

### Platform compatibility

<table>
	<tr>
		<th></th>
		<th>PHP</th>
		<th>MediaWiki</th>
		<th>Composer</th>
		<th>Validator</th>
	</tr>
	<tr>
		<th>SMW 1.9.x</th>
		<td>5.3.2 - 5.5.x</td>
		<td>1.19 - 1.23</td>
		<td>Required</td>
		<td>1.0.x (handled by Composer)</td>
	</tr>
	<tr>
		<th>SMW 1.8.x</th>
		<td>5.2.0 - 5.5.x</td>
		<td>1.17 - 1.22</td>
		<td>Not supported</td>
		<td>0.5.1</td>
	</tr>
	<tr>
		<th>SMW 1.7.1</th>
		<td>5.2.0 - 5.4.x</td>
		<td>1.16 - 1.19</td>
		<td>Not supported</td>
		<td>0.4.13 or 0.4.14</td>
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
		<td>Beta support</td>
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

Once you are done installing the Extension Installer, go to its directory so composer.phar
is installed in the right place.

    cd extensions/ExtensionInstaller

##### Step 2

If you have previously installed Composer skip to step 3.

To install Composer:

    wget http://getcomposer.org/composer.phar

##### Step 3
    
Now using Composer, install Semantic MediaWiki.

If you do not have a composer.json file yet, copy the composer-example.json file to composer.json. If you are using the ExtensionInstaller, the file to copy will be named example.json, rather than composer-example.json. When this is done, run:
    
    php composer.phar require mediawiki/semantic-media-wiki "1.9.*,>=1.9.0.0"

##### Step 4

Run the MediaWiki update script. The location of this script is maintenance/update.php. It can be run as follows:

    php maintenance/update.php

##### Verify installation success

As final step, you can verify SMW got installed by looking at the Special:Version page on your wiki and verifying the
Semantic MediaWiki section is listed.

### Using a tarball

As alternative to the first two installation steps, you can obtain the SMW code by getting one of the release tarballs.
These tarballs include all dependencies of SMW. This open exists mainly for those that have no command line access.
If you do, using the Composer approach is preferred.

* [Download tarball of the latest SMW release](https://sourceforge.net/projects/semediawiki/files/latest/download)
* [List of SMW tarballs](https://sourceforge.net/projects/semediawiki/files/semediawiki/)

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
require_once "$IP/extensions/SemanticMediaWiki/SemanticMediaWiki.php";
```

## More instructions

* [Verbose installation instructions](https://semantic-mediawiki.org/wiki/Help:Installation)
* [Upgrading instructions](https://semantic-mediawiki.org/wiki/Help:Installation#Upgrading)
* [Configuration instructions](https://semantic-mediawiki.org/wiki/Help:Configuration)
* [Administrator manual](https://semantic-mediawiki.org/wiki/Help:Administrator_manual)
