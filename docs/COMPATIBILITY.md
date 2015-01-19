For a full list of changes in each release, see the [release notes](releasenotes/). For instructions
on how to install the latest version of SMW, see the [installation instructions](INSTALL.md).

### SMW versions

<table>
	<tr>
		<th></th>
		<th>Status</th>
		<th>Release date</th>
		<th>Git branch</th>
	</tr>
	<tr>
		<th><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/RELEASE-NOTES.md">SMW 2.1.x</a></th>
		<td>Stable release</td>
		<td>2015-01-19</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.1.x">2.1.x</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.0">SMW 2.0</a></th>
		<td>Obsolete release</td>
		<td>2014-08-04</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.0">2.0</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.9.2">SMW 1.9.2</a></th>
		<td>Obsolete release</td>
		<td>2014-04-18</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.9.2">1.9.2</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.9.1">SMW 1.9.1</a></th>
		<td>Obsolete release</td>
		<td>2014-02-09</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.9.1">1.9.1</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.9.0">SMW 1.9.0</a></th>
		<td>Obsolete release</td>
		<td>2014-01-03</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.9">1.9</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.8.0">SMW 1.8.x</a></th>
		<td>Obsolete release</td>
		<td>2012-12-02</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.8.x">1.8.x</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.7.1">SMW 1.7.1</a></th>
		<td>Obsolete release</td>
		<td>2012-03-05</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.7.1">1.7.1</a></td>
	</tr>
</table>

### Platform compatibility

Releases after Composer support:

<table>
	<tr>
		<th></th>
		<th>PHP</th>
		<th>HHVM</th>
		<th>MediaWiki</th>
	</tr>
	<tr>
		<th>SMW 3.x</th>
		<td>5.5.x - latest (possibly 5.4.x)</td>
		<td>3.3.x - latest</td>
		<td>1.22 - latest</td>
	</tr>
	<tr>
		<th>SMW 2.1.x</th>
		<td>5.3.2 - 5.6.x</td>
		<td>3.3.x - 3.5.x</td>
		<td>1.19 - 1.24</td>
	</tr>
	<tr>
		<th>SMW 2.0.x</th>
		<td>5.3.2 - 5.6.x</td>
		<td>-</td>
		<td>1.19 - 1.23</td>
	</tr>
	<tr>
		<th>SMW 1.9.x</th>
		<td>5.3.2 - 5.6.x</td>
		<td>-</td>
		<td>1.19 - 1.22</td>
	</tr>
</table>

Releases before Composer support:

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
		<td>-</td>
		<td>1.17 - 1.22</td>
		<td>0.5.1</td>
	</tr>
	<tr>
		<th>SMW 1.7.1</th>
		<td>5.2.0 - 5.4.x</td>
		<td>-</td>
		<td>1.16 - 1.19</td>
		<td>0.4.13 or 0.4.14</td>
	</tr>
</table>


The PHP and MediaWiki version ranges listed are those in which SMW is known to work. It might also
work with more recent versions of PHP and MediaWiki, though this is not guaranteed.

### Database support

SQL databases:

<table>
	<tr>
		<th></th>
		<th>MySQL</th>
		<th>SQLite</th>
		<th>PostgreSQL</th>
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

Triple store databases:

<table>
	<tr>
		<th></th>
		<th><a href="https://jena.apache.org/">Jena Fuseki</a></th>
		<th><a href="https://github.com/openlink/virtuoso-opensource">Virtuoso Opensource</a></th>
		<th><a href="https://github.com/garlik/4store">4store</a></th>
		<th><a href="http://rdf4j.org/">Sesame</a></th>
	</tr>
	<tr>
		<th>SMW 2.x</th>
		<td>Full support (1.x >=1.1)</td>
		<td>Full support (6.x >=6.1)<sup>1</sup></td>
		<td>Beta support (1.x >=1.1)</td>
		<td>Full support (2.7.x)</td>
	</tr>
	<tr>
		<th>SMW 1.9.x</th>
		<td>No support</td>
		<td>Beta support</td>
		<td>Beta support</td>
		<td>Beta support</td>
	</tr>
	<tr>
		<th>SMW &lt; 1.9</th>
		<td>No support</td>
		<td>Experimental support</td>
		<td>Experimental support</td>
		<td>No support</td>
	</tr>
</table>

- `Full support` means that all functionality has been verified to work and that it can be used in production
- `Beta support` means that most functionality has been verified to work, though stability is still low, and things might be buggy
- `Experimental support` means there is some preliminary support which is still much too immature for use in production

The information in brackets denotes the versions with which SMW is known to work. SMW might also
work with different versions, especially more recent ones, though this is not guaranteed.

<sup>1</sup> Tested for 6.x but reported to work with Virtuoso 7

