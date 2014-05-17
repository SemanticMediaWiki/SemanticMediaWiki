# Semantic MediaWiki 1.9.3

Not a release yet.

### Bug fixes

* #279 Fixed undefined index in `DataTypeRegistry::getDefaultDataItemTypeId`
* #282 Output a message instead of an exception in `Special:WantedProperties` for unknown predefined properties
* #289 Added [``CONTRIBUTING.md``](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/CONTRIBUTING.md) for better contributor guidance
* #291 Fixed call to undefined method in `SMWSparqlStore::getDatabase` 
* #308 Fixed caching issue in `SMW\Store\Maintenance\DataRebuilder` for duplicate title objects
* #312 Fixed fatal error in `SMWCategoryResultPrinter` for when a mainlabel is hidden 

### Internal enhancements

* #278 Changed the `SMW\SQLStore\PropertyStatisticsTable` interface 
* #303 Adjusted `SMW\MediaWiki\Hooks\InternalParseBeforeLinks` to cope with changes in MW 1.23/MW 1.24 (bug 62856)
* #310 Fixed autoloading for `SMW\Test\QueryPrinterRegistryTestCase`
* #311 Removed `MediaWikiTestCase` dependency
* #315 Updated jquery.qTip2 from v2.0.0 to v2.2.0 (Mar 17 2014)
