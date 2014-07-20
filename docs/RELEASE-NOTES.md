# Semantic MediaWiki 2.0

Not a release yet.

### New features

#### SPARQLStore
* #342 Added `SparqlDBConnectionProvider` and [`$smwgSparqlDatabaseConnector`](https://semantic-mediawiki.org/wiki/Help:$smwgSparqlDatabaseConnector) as setting to avoid arbitrary class assignments in `$smwgSparqlDatabase`
* #339 Added `FusekiHttpDatabaseConnector` to support Jena Fuseki 1.0.2+
* #370 Extended `FourstoreHttpDatabaseConnector` to restore `4Store` (1.1.4) compliance (bug 43708, bug 44700)
* #387 Fixed `SPARQLStore` namespace query support (e.g `[[:+]]` ) 
* #394 Added [Virtuoso opensource 6.1](https://github.com/openlink/virtuoso-opensource) as service to Travis-CI to support automated `SPARQLStore` tests
* #337 Added [Jena Fuseki](http://jena.apache.org/) (1.0.2) as service to Travis-CI  to support automated `SPARQLStore` tests
* #360 Added `TurtleTriplesBuilder` to aid testability of the `SPARQLStore`
* #360, #371, #379, #375, #383, #392, #393, #395, #402, #403, #415 Modernized `SPARQLStore` implementation

#### Extended subobject suport
* #225 Added sorting annotation in subobjects using the `@sortkey` identifier
* #377 Added RDF export for subobjects (bug 48708)
* #378 Added subobject import to RDF stores (bug 48361)

### Bug fixes

* #279 Fixed undefined index in `DataTypeRegistry::getDefaultDataItemTypeId`
* #282 Output a message instead of an exception in `Special:WantedProperties` for unknown predefined properties
* #291 Fixed call to undefined method in `SPARQLStore` 
* #308 Fixed caching issue in `DataRebuilder` for duplicate title objects
* #312 Fixed fatal error in `CategoryResultPrinter` for when a mainlabel is hidden 
* #322 Fixed file names containing spaces or non-ASCII characters for for downloadable result formats (csv, excel)
* #338 Fixed exception in `SMWSparqlResultParser` for an invalid datatype (bug 62218)
* #379 Modernized `dumpRDF.php` while deprecating the use of `SMW_dumpRDFphp` (bug 35679)
* #385 Fixed '#' encoding for subobjects in `SMWExporter::findDataItemForExpElement` 

#### MediaWiki
* #420 Extended `ContentParser` to mitigate issues caused by bug 62856 in MW 1.24+
* #405 Added compatibility fix  to mitigate issues caused by `RefreshLinksJob` in MW 1.23+

### Internal enhancements

* #278 Changed the `PropertyStatisticsTable` interface 
* #289 Added [``CONTRIBUTING.md``](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/CONTRIBUTING.md) for better contributor guidance
* #307 Added `removeDuplicates` option to `UpdateJob`
* #310 Fixed autoloading for `QueryPrinterRegistryTestCase`
* #311 Removed `MediaWikiTestCase` dependency
* #315 Updated jquery.qTip2 from v2.0.0 to v2.2.0 (Mar 17 2014)
* #332 Added the number of pages and percentage done to report messages when rebuilding selected pages
* #349 Added `hhvm-nightly` as service to run tests on HHVM 3.2+
* #366 Added lazy load of SubSemanticData to `Sql3StubSemanticData` 
* #382 Extended interface to support `format=count` information in `QueryResult`
* #398, #404, #407, #409, #410, #411, #412, #416, #417, #418, #419, #421  namespace adjustments
