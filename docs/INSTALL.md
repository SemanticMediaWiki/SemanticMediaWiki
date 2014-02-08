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
		<th><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/RELEASE-NOTES.md">SMW 1.9.1.x</a></th>
		<td>Development version</td>
		<td>-</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master">master</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.9.1">SMW 1.9.1</a></th>
		<td>Stable release</td>
		<td>2014-02-09</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.9.1">1.9.1</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.9.0">SMW 1.9.0</a></th>
		<td>Legacy release</td>
		<td>2014-01-03</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.9">1.9</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.8.0">SMW 1.8.x</a></th>
		<td>Legacy release</td>
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
    
    php composer.phar require mediawiki/semantic-media-wiki "1.9.*,>=1.9.0.1"

##### Step 4

Run the MediaWiki update script. The location of this script is maintenance/update.php. It can be run as follows:

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
