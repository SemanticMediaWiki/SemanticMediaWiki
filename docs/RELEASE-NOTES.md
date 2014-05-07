# Semantic MediaWiki 1.9.3

Not a release yet.

### Bug fixes

* #279 Fixed undefined index in DataTypeRegistry::getDefaultDataItemTypeId
* #282 Catched exception in Special:WantedProperties for unknown predefined properties
* #289 Added [``CONTRIBUTING.md``](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/CONTRIBUTING.md) for better contributor guidance
* #291 Fixed call to undefined method in SMWSparqlStore::getDatabase 
* #308 Fixed caching to find duplicate title objects in SMW\Store\Maintenance\DataRebuilder

### Internal enhancements

* #278 Changed the SMW\SQLStore\PropertyStatisticsTable interface 
* #303 Adjusted SMW\MediaWiki\Hooks\InternalParseBeforeLinks to cope with changes in MW 1.23/MW 1.24 (bug 62856)
