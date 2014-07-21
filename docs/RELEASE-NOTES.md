# Semantic MediaWiki 2.0 RC1

First release candidate for SMW 2.0. Made available on 2014-07-21.

## New features

### SPARQLStore

[SMW 1.6](http://semantic-mediawiki.org/wiki/SMW_1.6#Synchronizing_SMW_with_RDF_stores) introduced
support for data synchronization with RDF back-ends. SMW 2.0 extends the existing implementation and
adds an additional database connector for [Jena Fuseki](http://jena.apache.org/) 1.0.2 (#339). It
also restores the support for `4Store` 1.1.4  (#370, bug 43708, bug 44700). Other fixes include:

- #291 Fixed call to undefined method in `SPARQLStore`
- #338 Fixed exception in `ResultParser` for an invalid datatype (bug 62218)
- #385 Fixed '#' encoding for subobjects in `SMWExporter::findDataItemForExpElement` to enable `SPARQLStore` result display
- #387 Fixed `SPARQLStore` namespace query support (e.g `[[:+]]` )
- #415 Fixed `SPARQLStore` usage for `rebuildConceptCache.php` and `rebuildPropertyStatistics.php`

The `smwgSparqlDatabase` setting introduced in 1.6 has been deprecated in favour of
[`$smwgSparqlDatabaseConnector`](https://semantic-mediawiki.org/wiki/Help:$smwgSparqlDatabaseConnector)
(#342) to avoid arbitrary class assignments in `$smwgSparqlDatabase` (now only used to assign custom
connectors).

`SPARQLStore` has been converted to make use of a separate namespace together with improvements that
allow for better testability and code readability (#360, #371, #379, #375, #383, #392, #393, #395,
#402, #403, #415).

The `SMWSparqlStore` and `SMWSparqlDatabase` class names are kept for legacy support but it is
suggested to use the and settings parameter.

Unit and integration tests were given extra consideration so that any core change will also be
tested against [Jena Fuseki](http://jena.apache.org/) (1.0.2) (#337) and [Virtuoso opensource 6.1]
(https://github.com/openlink/virtuoso-opensource) (#394) to ensure that compatibility and functional
parity are going hand in hand with the rest of SMW. (Unfortunately `4Store` currently does not run
on the continues integration platform, for details see [garlik#110]
(https://github.com/garlik/4store/issues/110)but tests have been run successfully with a local
`4store` instance).

Details to the testing environment and its settings can be found [here]
(https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/includes/src/SPARQLStore/README.md)

### RDF subobject support

Previous releases came without RDF subobject support but this has been corrected in 2.0. The RDF
subobject export (#377, bug 48708) and RDF store subobject synchronization (#378, bug 48361) are
fixed and come with appropriate test coverage in order to make the `SQLStore` and the `SPARQLStore`
equally supported.

Subobject do now support a sort annotation by using the `@sortkey` identifier (#225) that stores an
individual sortkey per subobject.

### Continues integration

Continues integration is now an integral part of SMW to predict the impact of changes in terms of
expected behaviour and functionality. At the time of its release, SMW runs 2250+ tests which amounts
for approximately 62% code coverage in total.

The continues integration platform not only allows to run services such as those used for the
`SPARQLStore` integration but also used to test against cutting edge environments such as [HHVM]
(http://hhvm.com/) (#349). SMW's test suite does successfully pass all its tests on HHVM except for
those that are blocked by [facebook/hhvm#2829](https://github.com/facebook/hhvm/issues/2829).

### Bug fixes

* #279 Fixed undefined index in `DataTypeRegistry::getDefaultDataItemTypeId`
* #282 Output a message instead of an exception in `Special:WantedProperties` for unknown predefined properties
* #308 Fixed caching issue in `DataRebuilder` for duplicate title objects
* #312 Fixed fatal error in `CategoryResultPrinter` for when a mainlabel is hidden
* #322 Fixed file names containing spaces or non-ASCII characters for for downloadable result formats (csv, excel)
* #379 Modernized `dumpRDF.php` while deprecating the use of `SMW_dumpRDF.php` (bug 35679)
* #425 Deprecated `SMW_setup.php` in favour of `setupStore.php`
* #444 Fixed language namespace alias issue 

#### Hot fixes for MediaWiki related bugs

* #420 Extended `ContentParser` to mitigate issues caused by the 62856 bug in MW 1.24+
* #405 Added a compatibility fix to mitigate issues caused by the `RefreshLinksJob` in MW 1.23+

## Internal enhancements (development)

SMW 2.0 continues to convert its classes to use PHP namespaces in order to separate responsibilities
(#398, #404, #407, #409, #410, #411, #412, #416, #417, #418, #419, #421) and to be able to support
[PSR-4](http://www.php-fig.org/psr/psr-4/) in future.

* All `job` related classes of been moved to `SMW\MediaWiki\Jobs`
* All `hook` related classes of been moved to `SMW\MediaWiki\Hooks`
* All `api` related classes of been moved to `SMW\MediaWiki\Api`
* All `SPARQLStore` related classes now reside in `SMW\SPARQLStore`

Other internal enhancements or changes include:

* #278 Changed the `PropertyStatisticsTable` interface
* #289 Added [`CONTRIBUTING.md`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/CONTRIBUTING.md) for better contributor guidance
* #307 Added `removeDuplicates` option to `UpdateJob`
* #310 Fixed autoloading for `QueryPrinterRegistryTestCase`
* #311 Removed `MediaWikiTestCase` dependency
* #315 Updated jquery.qTip2 from v2.0.0 to v2.2.0 (Mar 17 2014)
* #332 Added the number of pages and percentage done to report messages when rebuilding selected pages
* #366 Added lazy load of SubSemanticData to `Sql3StubSemanticData`
* #382 Extended interface to support `format=count` information in `QueryResult`

### Deprecated classes or scripts

* `SMW_conceptCache.php`
* `SMW_dumpRDF.php`
* `SMW_refreshData.php`
* `SMW_setup.php`
* `SMWSparqlStore`
* `SMWSparqlDatabase`
* `SMWIResultPrinter`

### Removed classes or scripts

* `SMWParseData`
