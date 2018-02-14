For a full list of changes in each release, see the [release notes](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/docs/releasenotes). For instructions
on how to install the latest version of Semantic MediaWiki (SMW), see the [installation instructions](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/INSTALL.md).


## Release versions

<table>
	<tr>
		<th></th>
		<th>Status</th>
		<th>Release date</th>
		<th>Git branch</th>
	</tr>
	<tr>
		<th><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/milestone/6">SMW 3.0.x</a></th>
		<td>Development version</td>
		<td>Q4 2017 or Q1 2018</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master">master</a></td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.5.0">SMW 2.5.x</a></th>
		<td>Stable version</td>
		<td>2017-03-14</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x">master</a></td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.4.0">SMW 2.4.x</a></th>
		<td>Obsolete release</td>
		<td>2016-07-09</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.4.x">2.4.x</a></td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.3.0">SMW 2.3.x</a></th>
		<td>Obsolete release</td>
		<td>2015-10-25</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.3.x">2.3.x</a></td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.2.0">SMW 2.2.x</a></th>
		<td>Obsolete release</td>
		<td>2015-05-09</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.2.x">2.2.x</a></td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.1.x">SMW 2.1.x</a></th>
		<td>Obsolete release</td>
		<td>2015-01-19</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.1.x">2.1.x</a></td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.0">SMW 2.0.x</a></th>
		<td>Obsolete release</td>
		<td>2014-08-04</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.0.x">2.0.x</a></td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.9.2">SMW 1.9.2</a></th>
		<td>Obsolete release</td>
		<td>2014-04-18</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.9.2">1.9.2</a></td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.9.1">SMW 1.9.1</a></th>
		<td>Obsolete release</td>
		<td>2014-02-09</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.9.1">1.9.1</a></td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.9.0">SMW 1.9.0</a></th>
		<td>Obsolete release</td>
		<td>2014-01-03</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.9">1.9</a></td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.8.0">SMW 1.8.x</a></th>
		<td>Obsolete release</td>
		<td>2012-12-02</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.8.x">1.8.x</a></td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.7.1">SMW 1.7.1</a></th>
		<td>Obsolete release</td>
		<td>2012-03-05</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.7.1">1.7.1</a></td>
	</tr>
</table>


## Platform compatibility

The PHP and MediaWiki version ranges listed are those in which SMW is known to work. It might also
work with more recent versions of PHP and MediaWiki, though this is not guaranteed. Increases of
minimum requirements are indicated in bold.

Note that HHVM is only required if you do not use PHP.

<table>
	<tr>
		<th></th>
		<th>PHP</th>
		<th>HHVM</th>
		<th>MediaWiki</th>
	</tr>
	<tr>
		<th>SMW 3.0.x</th>
		<td><strong><a href="https://php.net/supported-versions.php">5.6.0</a></strong> - latest</td>
		<td>3.5.0 - latest</td>
		<td><strong><a href="https://www.mediawiki.org/wiki/Version_lifecycle">1.27.0</a></strong> - latest</td>
	</tr>
	<tr>
		<th>SMW 2.5.x</th>
		<td><strong><a href="https://php.net/supported-versions.php">5.5.0</a></strong> - latest</td>
		<td>3.5.0 - latest</td>
		<td><strong><a href="https://www.mediawiki.org/wiki/Version_lifecycle">1.23.0</a></strong> - 1.30</td>
	</tr>
	<tr>
		<th>SMW 2.4.x</th>
		<td>5.3.2 - 7.0</td>
		<td>3.5.0 - 3.9.x</td>
		<td>1.19 - 1.27</td>
	</tr>
	<tr>
		<th>SMW 2.3.x</th>
		<td>5.3.2 - 5.6.x</td>
		<td><strong>3.5.0</strong> - 3.9.x</td>
		<td>1.19 - 1.25</td>
	</tr>
	<tr>
		<th>SMW 2.2.x</th>
		<td>5.3.2 - 5.6.x</td>
		<td>3.3.0 - 3.9.x</td>
		<td>1.19 - 1.25</td>
	</tr>
	<tr>
		<th>SMW 2.1.x</th>
		<td>5.3.2 - 5.6.x</td>
		<td>3.3.0 - 3.5.x</td>
		<td>1.19 - 1.24</td>
	</tr>
	<tr>
		<th>SMW 2.0.x</th>
		<td>5.3.2 - 5.6.x</td>
		<td>No support</td>
		<td>1.19 - 1.23</td>
	</tr>
	<tr>
		<th>SMW 1.9.x</th>
		<td>5.3.2 - 5.6.x</td>
		<td>No support</td>
		<td>1.19 - 1.22</td>
	</tr>
</table>

* PHP 7.1+ requires at least MediaWiki 1.29 due to [T153505](https://phabricator.wikimedia.org/T153505) and [T143788](https://phabricator.wikimedia.org/T143788) (at the time of this writing). Please consult the official MediaWiki release documentation. 
* For the 1.28 MediaWiki release branch, 1.28.1 is recommended due to [T154428](https://phabricator.wikimedia.org/T154428).

**Releases before Composer support:**

<table>
	<tr>
		<th></th>
		<th>PHP</th>
		<th>HHVM</th>
		<th>MediaWiki</th>
		<th>Validator</th>
	</tr>
	<tr>
		<th>SMW 1.8.x</th>
		<td>5.2.0 - 5.5.x</td>
		<td>No support</td>
		<td>1.17 - 1.22</td>
		<td>0.5.1</td>
	</tr>
	<tr>
		<th>SMW 1.7.1</th>
		<td>5.2.0 - 5.4.x</td>
		<td>No support</td>
		<td>1.16 - 1.19</td>
		<td>0.4.13 or 0.4.14</td>
	</tr>
</table>


## Database compatibility

### SQL support

<table>
	<tr>
		<th></th>
		<th>MySQL</th>
		<th>SQLite</th>
		<th>PostgreSQL</th>
	</tr>
	<tr>
		<th>SMW 2.5.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>SMW 2.4.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>SMW 2.3.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>SMW 2.2.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>SMW 2.1.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>SMW 2.0.x</th>
		<td>Full support</td>
		<td>Full support</td>
		<td>Beta support</td>
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
		<td>Experimental support</td>
	</tr>
	<tr>
		<th>SMW 1.7.1</th>
		<td>Full support</td>
		<td>Experimental support</td>
		<td>No support</td>
	</tr>
</table>

Note that MS SQL Server and Oracle are not supported as database backends.

### Triple store support

<table>
	<tr>
		<th></th>
		<th><a href="https://jena.apache.org/">Fuseki</a></th>
		<th><a href="https://github.com/openlink/virtuoso-opensource">Virtuoso</a></th>
		<th><a href="https://github.com/garlik/4store">4store</a></th>
		<th><a href="http://rdf4j.org/">Sesame</a></th>
		<th><a href="https://wiki.blazegraph.com/">Blazegraph</a></th>
	</tr>
	<tr>
		<th>SMW 2.5.x</th>
		<td>Full support<br />(1.x >=1.1) + 2.4.0</td>
		<td>Full support<br />(6.x >=6.1) + 7.2<sup>[t.1]</sup></td>
		<td>Beta support<br />(1.x >=1.1)<sup>[t.2]</sup></td>
		<td>Full support<br />(2.8.x)</td>
		<td>Full support<br />(1.5.2) + 2.1.0<sup>[t.3]</sup></td>
	</tr>
	<tr>
		<th>SMW 2.4.x</th>
		<td>Full support<br />(1.x >=1.1) + 2.4.0</td>
		<td>Full support<br />(6.x >=6.1) + 7.2<sup>[t.1]</sup></td>
		<td>Beta support<br />(1.x >=1.1)<sup>[t.2]</sup></td>
		<td>Full support<br />(2.8.x)</td>
		<td>Full support<br />(1.5.2) + 2.1.0<sup>[t.3]</sup></td>
	</tr>
	<tr>
		<th>SMW 2.3.x</th>
		<td>Full support<br />(1.x >=1.1)</td>
		<td>Full support<br />(6.x >=6.1) + 7.1<sup>[t.1]</sup></td>
		<td>Beta support<br />(1.x >=1.1)<sup>[t.2]</sup></td>
		<td>Full support<br />(2.7.x)</td>
		<td>Full support<br />(1.5.2)</td>
	</tr>
	<tr>
		<th>SMW 2.2.x</th>
		<td>Full support<br />(1.x >=1.1)</td>
		<td>Full support<br />(6.x >=6.1) + 7.1<sup>[t.1]</sup></td>
		<td>Beta support<br />(1.x >=1.1)<sup>[t.2]</sup></td>
		<td>Full support<br />(2.7.x)</td>
		<td>Beta support<br />(1.5.2)</td>
	</tr>
	<tr>
		<th>SMW 2.1.x</th>
		<td>Full support<br />(1.x >=1.1)</td>
		<td>Full support<br />(6.x >=6.1)</td>
		<td>Beta support<br />(1.x >=1.1)</td>
		<td>Full support<br />(2.7.x)</td>
		<td>Not tested</td>
	</tr>
	<tr>
		<th>SMW 2.0.x</th>
		<td>Full support</td>
		<td>Full support</td>
		<td>Beta support</td>
		<td>Beta support</td>
		<td>Not tested</td>
	</tr>
	<tr>
		<th>SMW 1.9.x</th>
		<td>No support</td>
		<td>Beta support</td>
		<td>Beta support</td>
		<td>Beta support</td>
		<td>Not tested</td>
	</tr>
	<tr>
		<th>SMW &lt; 1.9</th>
		<td>No support</td>
		<td>Experimental support</td>
		<td>Experimental support</td>
		<td>No support</td>
		<td>Not tested</td>
	</tr>
</table>

- "Full support" means that all functionality has been verified to work and that it can be used in production
- "Beta support" means that most functionality has been verified to work, though stability is still low, and things might be buggy
- "Experimental support" means there is some preliminary support which is still much too immature for use in production

The information in brackets denotes the versions with which SMW is known to work. SMW might also
work with different versions, especially more recent ones, though this is not guaranteed.

## Notes

- <sup>[t.1]</sup> On an irregular test plan with [Virtuoso 7.2](https://travis-ci.org/mwjames/SemanticMediaWiki/builds/97294290)
- <sup>[t.2]</sup> On an irregular test plan with [4store 1.1.4](https://travis-ci.org/mwjames/SemanticMediaWiki/builds/61200454)
- <sup>[t.3]</sup> [#1583](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1583)
