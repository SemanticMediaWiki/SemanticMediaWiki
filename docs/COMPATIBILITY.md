For a full list of changes in each release, see the [release notes](releasenotes/). For instructions
on how to install the latest version of SMW, see the [installation instructions](INSTALL.md).

### Recent releases

<table>
	<tr>
		<th></th>
		<th>Status</th>
		<th>Release date</th>
		<th>Git branch</th>
	</tr>
	<tr>
		<th><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/RELEASE-NOTES.md">SMW 2.1.x</a></th>
		<td>Development version</td>
		<td>-</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master">master</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_2.0">SMW 2.0</a></th>
		<td>Stable release</td>
		<td>2014-08-04</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.0">2.0</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.9.2">SMW 1.9.2</a></th>
		<td>Legacy release</td>
		<td>2014-04-18</td>
		<td><a href="https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/1.9.2">1.9.2</a></td>
	</tr>
	<tr>
		<th><a href="https://semantic-mediawiki.org/wiki/Semantic_MediaWiki_1.9.1">SMW 1.9.1</a></th>
		<td>Legacy release</td>
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
		<th>SMW 2.x</th>
		<td>5.3.2 - 5.6.x</td>
		<td>1.19 - 1.23</td>
		<td>Required</td>
		<td>2.x (handled by Composer)</td>
	</tr>
	<tr>
		<th>SMW 1.9.x</th>
		<td>5.3.2 - 5.6.x</td>
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

- `Full support` means that all functionality has been verified to work and that it can be used in production
- `Beta support` means that most functionality has been verified to work, though stability is still low, and things might be buggy
- `Experimental` means there is some preliminary support which is still much too immature for use in production

SQL databases:

<table>
	<tr>
		<th></th>
		<th>MySQL</th>
		<th>SQLite</th>
		<th>PostgreSQL</th>
	</tr>
	<tr>
		<th>SMW 2.x</th>
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
		<td>Experimental</td>
	</tr>
	<tr>
		<th>SMW 1.7.1</th>
		<td>Full support</td>
		<td>Experimental</td>
		<td>None</td>
	</tr>
</table>

Triple store databases:

<table>
	<tr>
		<th></th>
		<th><a href="https://jena.apache.org/">Jena Fuseki</a></th>
		<th><a href="https://github.com/openlink/virtuoso-opensource">Virtuoso Opensource</a></th>
		<th><a href="https://github.com/garlik/4store">4store</a></th>
	</tr>
	<tr>
		<th>SMW 2.x</th>
		<td>Full support (for 1.0)</td>
		<td>Full support (for 6.1)</td>
		<td>Beta support (for 1.1)</td>
	</tr>
	<tr>
		<th>SMW 1.9.x</th>
		<td>None</td>
		<td>Beta support</td>
		<td>Beta support</td>
	</tr>
	<tr>
		<th>SMW &lt; 1.9</th>
		<td>None</td>
		<td>Experimental</td>
		<td>Experimental</td>
	</tr>
</table>
