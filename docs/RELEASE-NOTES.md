# Semantic MediaWiki 1.9.3

Not a release yet.

### New features

* #225 Added sorting annotation in subobjects using the `@sortkey` identifier
* #342 Added `SparqlDBConnectionProvider` and [`$smwgSparqlDatabaseConnector`](https://semantic-mediawiki.org/wiki/Help:$smwgSparqlDatabaseConnector) as setting to avoid arbitrary class assignments in `$smwgSparqlDatabase`

### Bug fixes

* #279 Fixed undefined index in `DataTypeRegistry::getDefaultDataItemTypeId`
* #282 Output a message instead of an exception in `Special:WantedProperties` for unknown predefined properties
* #291 Fixed call to undefined method in `SMWSparqlStore::getDatabase` 
* #308 Fixed caching issue in `SMW\Store\Maintenance\DataRebuilder` for duplicate title objects
* #312 Fixed fatal error in `SMWCategoryResultPrinter` for when a mainlabel is hidden 
* #322 Fixed file names containing spaces or non-ASCII characters for for downloadable result formats (csv, excel)
* #338 Fixed exception in `SMWSparqlResultParser` for an invalid datatype (bug 62218)

### Internal enhancements

* #278 Changed the `SMW\SQLStore\PropertyStatisticsTable` interface 
* #289 Added [``CONTRIBUTING.md``](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/CONTRIBUTING.md) for better contributor guidance
* #307 Added `removeDuplicates` option to `SMW\MediaWiki\Jobs\UpdateJob`
* #310 Fixed autoloading for `SMW\Test\QueryPrinterRegistryTestCase`
* #311 Removed `MediaWikiTestCase` dependency
* #315 Updated jquery.qTip2 from v2.0.0 to v2.2.0 (Mar 17 2014)
* #332 Added the number of pages and percentage done to report messages when rebuilding selected pages
* #337 Added [Jena Fuseki](http://jena.apache.org/) (1.0.2) as service on Travis to run `SMWSparqlStore` tests
* #339 Added `FusekiHttpDatabaseConnector` to support Jena Fuseki 1.0.2+
