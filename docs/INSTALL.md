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
		<th><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/releasenotes/RELEASE-NOTES-1.8.md">SMW 1.8.x</a></th>
		<td>Stable release</td>
		<td>2012-12-02</td>
		<td>5.2.0 - 5.5.x</td>
		<td>1.17 - 1.22</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.8.x">1.8.x</a></td>
	</tr>
	<tr>
		<th><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/releasenotes/RELEASE-NOTES-1.7.1.md">SMW 1.7.1</a></th>
		<td>Legacy release</td>
		<td>2012-03-05</td>
		<td>5.2.0 - 5.4.x</td>
		<td>1.16 - 1.19</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.7.1">1.7.1</a></td>
	</tr>
</table>

The PHP and MediaWiki version ranges listed are those in which SMW is known to work. It might also
work with more recent versions of PHP and MediaWiki, though this is not guaranteed.

## Download and installation

### With Composer

The recommended way to install Semantic MediaWiki is with [Composer](http://getcomposer.org).
See the [extension installation with Composer](https://www.mediawiki.org/wiki/Composer) instructions.

The package name is "mediawiki/semantic-mediawiki", so your composer.json file should look as follows:

```javascript
{
	"require": {
		// ...
		"mediawiki/semantic-mediawiki": "~1.9.0"
	},
	"minimum-stability" : "dev"
}
```

The "minimum-stability" section needs to be added as well for now.
This need for this will be removed when SMW 1.9 is released.

### Manual installation

Alternatively you can obtain the SMW code and the code of all its dependencies yourself, and load them all.

You can find a list of the dependencies in the "requires" section of the [composer.json file]
(../composer.json). These packages are also linked on the [SMW Packagist page]
(https://packagist.org/packages/mediawiki/semantic-mediawiki).

You can get the SMW code itself:

* Via git: git clone https://github.com/SemanticMediaWiki/SemanticMediaWiki.git
* As Tarball: https://github.com/SemanticMediaWiki/SemanticMediaWiki/releases

The only remaining step is to include SemanticMediaWiki in your LocalSettings.php file:

```php
require_once( "$IP/extensions/SemanticMediaWiki/SemanticMediaWiki.php" );
```