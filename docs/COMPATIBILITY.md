# Compatibility

This document holds the compatibility information for Semantic MediaWiki (SMW).

For a full list of changes in each release, see the [release notes](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/docs/releasenotes).
For instructions on how to install the latest version of Semantic MediaWiki (SMW), see the
[installation instructions](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/INSTALL.md).

## Platform compatibility and release status

<table class="compatibility">
	<tr>
		<th>SMW</th>
		<th>PHP</th>
		<th>MediaWiki</th>
		<th>Released</th>
		<th>Status</th>
	</tr>
	<tr>
		<th>3.2.x</th>
		<td><strong>7.1.0</strong> - latest</td>
		<td>1.31.0 - 1.35.x</td>
		<td>Q2.2020</td>
		<td>Future</td>
	</tr>
	<tr>
		<th><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/milestone/23">3.1.x</a></th>
		<td><strong>7.0.0</strong> - latest</td>
		<td><strong>1.31.0</strong> - 1.33.x</td>
		<td>2019-09-23</td>
		<td>Current</td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_3.0.0">3.0.x</a></th>
		<td><strong>5.6.0</strong> - 7.2.x</td>
		<td><strong>1.27.0</strong> - 1.31.x</td>
		<td>2018-10-11</td>
		<td>Obsolete</td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.5.0">2.5.x</a></th>
		<td><strong>5.5.0</strong> - 7.1.x</td>
		<td><strong>1.23.0</strong> - 1.30.x</td>
		<td>2017-03-14</td>
		<td>Obsolete</td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.4.0">2.4.x</a></th>
		<td>5.3.2 - 7.0.x</td>
		<td>1.19.0 - 1.27.x</td>
		<td>2016-07-09</td>
		<td>Obsolete</td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.3.0">2.3.x</a></th>
		<td>5.3.2 - 5.6.x</td>
		<td>1.19.0 - 1.25.x</td>
		<td>2015-10-25</td>
		<td>Obsolete</td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.2.0">2.2.x</a></th>
		<td>5.3.2 - 5.6.x</td>
		<td>1.19.0 - 1.25.x</td>
		<td>2015-05-09</td>
		<td>Obsolete</td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.1.0">2.1.x</a></th>
		<td>5.3.2 - 5.6.x</td>
		<td>1.19.0 - 1.24.x</td>
		<td>2015-01-19</td>
		<td>Obsolete</td>
	</tr>
	<tr>
		<th><a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.1.0">2.1.x</a></th>
		<td>5.3.2 - 5.6.x</td>
		<td>1.19.0 - 1.23.x</td>
		<td>2014-08-04</td>
		<td>Obsolete</td>
	</tr>
	<tr>
		<th>1.9.x</th>
		<td>5.3.2 - 5.6.x</td>
		<td>1.19.0 - 1.22.x</td>
		<td>2014-01-03</td>
		<td>Obsolete</td>
	</tr>
</table>

### Notes

The PHP and MediaWiki version ranges listed are those in which SMW is known to work. It might also work with more recent versions of PHP and MediaWiki, though this is not guaranteed. Increases of minimum requirements are indicated in bold.

It is strongly recommended to also always upgrade the underlying MediaWiki software to supported versions. See the [version lifecycle](https://www.mediawiki.org/wiki/Version_lifecycle) for current information on supported versions.

* For the 1.28 MediaWiki release branch, 1.28.1 or later is recommended due to [T154428](https://phabricator.wikimedia.org/T154428).
* For the 1.27 MediaWiki release branch, 1.27.4 or later is recommended due to [T100085](https://phabricator.wikimedia.org/T100085).
* PHP 7.1+ requires at least MediaWiki 1.29 due to [T153505](https://phabricator.wikimedia.org/T153505) and [T143788](https://phabricator.wikimedia.org/T143788) (at the time of this writing). Please also consult the official MediaWiki release documentation.

HHVM 3.3.0 to 3.30.0 which was only needed when not using PHP was supported in varying versions from SMW 2.1.x to 3.0.x
but since it is no longer tested functionality can no longer be validated and assured.

## Database compatibility

### SQL support

<table class="compatibility">
	<tr>
		<th>SMW</th>
		<th>MySQL</th>
		<th>SQLite</th>
		<th>PostgreSQL</th>
	</tr>
	<tr>
		<th>3.1.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>3.0.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>2.5.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>2.4.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>2.3.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>2.2.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>2.1.x</th>
		<td>Full support (5.x)</td>
		<td>Full support (3.x)</td>
		<td>Full support (9.x)</td>
	</tr>
	<tr>
		<th>2.0.x</th>
		<td>Full support</td>
		<td>Full support</td>
		<td>Beta support</td>
	</tr>
	<tr>
		<th>1.9.x</th>
		<td>Full support</td>
		<td>Full support</td>
		<td>Beta support</td>
	</tr>
</table>

Note that MS SQL Server and Oracle are not supported as database backends.

### Triple store support

<table class="compatibility">
	<tr>
		<th>SMW</th>
		<th><a href="https://jena.apache.org/">Fuseki</a></th>
		<th><a href="https://github.com/openlink/virtuoso-opensource">Virtuoso</a></th>
		<th><a href="https://github.com/garlik/4store">4store</a></th>
		<th><a href="http://rdf4j.org/">Sesame</a></th>
		<th><a href="https://wiki.blazegraph.com/">Blazegraph</a></th>
	</tr>
	<tr>
		<th>3.1.x</th>
		<td>Full support<br />(1.x >=1.1) + 2.4.0</td>
		<td>Full support<br />(6.x >=6.1) + 7.2<sup>[t.1]</sup></td>
		<td>Beta support<br />(1.x >=1.1)<sup>[t.2]</sup></td>
		<td>Full support<br />(2.8.x)</td>
		<td>Full support<br />(1.5.2) + 2.1.0<sup>[t.3]</sup></td>
	</tr>
	<tr>
		<th>3.0.x</th>
		<td>Full support<br />(1.x >=1.1) + 2.4.0</td>
		<td>Full support<br />(6.x >=6.1) + 7.2<sup>[t.1]</sup></td>
		<td>Beta support<br />(1.x >=1.1)<sup>[t.2]</sup></td>
		<td>Full support<br />(2.8.x)</td>
		<td>Full support<br />(1.5.2) + 2.1.0<sup>[t.3]</sup></td>
	</tr>
	<tr>
		<th>2.5.x</th>
		<td>Full support<br />(1.x >=1.1) + 2.4.0</td>
		<td>Full support<br />(6.x >=6.1) + 7.2<sup>[t.1]</sup></td>
		<td>Beta support<br />(1.x >=1.1)<sup>[t.2]</sup></td>
		<td>Full support<br />(2.8.x)</td>
		<td>Full support<br />(1.5.2) + 2.1.0<sup>[t.3]</sup></td>
	</tr>
	<tr>
		<th>2.4.x</th>
		<td>Full support<br />(1.x >=1.1) + 2.4.0</td>
		<td>Full support<br />(6.x >=6.1) + 7.2<sup>[t.1]</sup></td>
		<td>Beta support<br />(1.x >=1.1)<sup>[t.2]</sup></td>
		<td>Full support<br />(2.8.x)</td>
		<td>Full support<br />(1.5.2) + 2.1.0<sup>[t.3]</sup></td>
	</tr>
	<tr>
		<th>2.3.x</th>
		<td>Full support<br />(1.x >=1.1)</td>
		<td>Full support<br />(6.x >=6.1) + 7.1<sup>[t.1]</sup></td>
		<td>Beta support<br />(1.x >=1.1)<sup>[t.2]</sup></td>
		<td>Full support<br />(2.7.x)</td>
		<td>Full support<br />(1.5.2)</td>
	</tr>
	<tr>
		<th>2.2.x</th>
		<td>Full support<br />(1.x >=1.1)</td>
		<td>Full support<br />(6.x >=6.1) + 7.1<sup>[t.1]</sup></td>
		<td>Beta support<br />(1.x >=1.1)<sup>[t.2]</sup></td>
		<td>Full support<br />(2.7.x)</td>
		<td>Beta support<br />(1.5.2)</td>
	</tr>
	<tr>
		<th>2.1.x</th>
		<td>Full support<br />(1.x >=1.1)</td>
		<td>Full support<br />(6.x >=6.1)</td>
		<td>Beta support<br />(1.x >=1.1)</td>
		<td>Full support<br />(2.7.x)</td>
		<td>Not tested</td>
	</tr>
	<tr>
		<th>2.0.x</th>
		<td>Full support</td>
		<td>Full support</td>
		<td>Beta support</td>
		<td>Beta support</td>
		<td>Not tested</td>
	</tr>
	<tr>
		<th>1.9.x</th>
		<td>No support</td>
		<td>Beta support</td>
		<td>Beta support</td>
		<td>Beta support</td>
		<td>Not tested</td>
	</tr>
</table>

- "Full support" means that all functionality has been verified to work and that it can be used in production
- "Beta support" means that most functionality has been verified to work, though stability is still low, and things might be buggy

### Notes

The information in brackets denotes the versions with which SMW is known to work. SMW might also work with different versions, especially more recent ones, though this is not guaranteed.

* <sup>[t.1]</sup> On an irregular test plan with [Virtuoso 7.2](https://travis-ci.org/mwjames/SemanticMediaWiki/builds/97294290)
* <sup>[t.2]</sup> On an irregular test plan with [4store 1.1.4](https://travis-ci.org/mwjames/SemanticMediaWiki/builds/61200454)
* <sup>[t.3]</sup> [#1583](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1583)
