# Semantic MediaWiki 1.9.2

Released April 18th, 2014.

### New features

* #199 Added [object-Id lookup](https://www.semantic-mediawiki.org/wiki/Help:Object_ID_lookup) to SMWAdmin
* #217 Extended ListResultPrinter to make `userparam` parameter available to intro and outro templates and introduced [additional parameters](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/217)
* #243 Added `--query` parameter to the `SMW_refreshData.php` maintenance script

### Bug fixes

* #203 Fixed undefined index in connection with `$smwgQuerySources`
* #215 (Bug 62150) Fixed malformed query order in SparqlQueryEngine
* #272 #276 Fixed ConceptCache on SQLite
* #273 Fixed footer logo path for windows based installations

### Deprecated 

* #187 Use of `SMW_conceptCache.php` has been deprecated in favour of `rebuildConceptCache.php`
* #244 Use of `SMW_refreshData.php` has been deprecated in favour of `rebuildData.php`

### Internal enhancements

* #195 Improvement of handling configuration settings in getPropertyTables
* #204 Added update job for new redirects
* #207 Removed SMWParamSource and added a `$smwgQuerySources` integration test
* #218 Extended SparqlStore to inject a SparqlDatabase to enable basic test coverage
* #226 Added ExecutionTimeTestListener to report long running tests
* #227 Moved all job related classes into the `SMW\MediaWiki\Jobs\` namespace
* #234 Added redirects regression test
* #236, #265 Removed wfGetDB from SMWSQLStore3Writers and extended test coverage
* #244, #253, #256, #267, #268 Refactored and migrate `SMW_refreshData.php` to `rebuildData.php`
* #248 Added support for the new MediaWiki i18n JSON system
* #256 Improved data selection for DataRebuilder filters
* #262 Improved SQLStore upgrading for Postgres
* #270 Removed SQLStore3::getPropertyTables as static caller
