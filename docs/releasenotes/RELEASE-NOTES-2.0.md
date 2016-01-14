# Semantic MediaWiki 2.0

Released August 4th, 2014.

## Compatibility changes

Semantic MediaWiki 2.0 is compatible with MediaWiki 1.19 up to MediaWiki 1.23, and possibly later
versions. Support for both MediaWiki 1.23 and MediaWiki 1.24 was improved compared to SMW 1.9.

PHP compatibility remains the same as in SMW 1.9: all versions from PHP 5.3.2 to PHP 5.6.x.


For a full overview, see our [compatibility matrix](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/COMPATIBILITY.md).

## Quality and stability improvements

A great deal of effort has been put into ensuring both existing and new features work well.
Not just at present, but also in future releases. And not just with MySQL and one version of
MediaWiki, but on all platforms we support. This dedication to quality has resulted in many
bugs being discovered and fixed, and makes future regressions much less likely.

Continuous integration is now an integral part of the development process behind SMW. As of
the 2.0 release, SMW has over 2300 automated tests, which cover two thirds of the codebase.
These tests are run automatically for every change made to the code, on machines with different
databases, different versions of PHP, different SPARQL stores and different versions of MediaWiki.

## Semantic Versioning

As of the 2.0 release, Semantic MediaWiki adheres to the [Semantic Versioning standard](http://semver.org/).
This makes our version numbers more meaningful and makes it easier for administrators to determine
if a new release is relevant to them.

## Improved SPARQLStore support

[Semantic MediaWiki 1.6](http://www.semantic-mediawiki.org/wiki/SMW_1.6#Synchronizing_SMW_with_RDF_stores)
introduced support for data synchronization with RDF back-ends. SMW 2.0 makes this functionality a
first class citizen through many enhancements and stability improvements.

* New and full support for [Jena Fuseki](http://jena.apache.org/) 1.0
* Enhanced and full support for [Virtuoso](https://github.com/openlink/virtuoso-opensource) 6.1
* Enhanced support for [4store](https://github.com/garlik/4store) 1.1

The [`smwgSparqlDatabase`](https://www.semantic-mediawiki.org/wiki/Help:$smwgSparqlDatabase) setting
introduced in 1.6 has been deprecated in favour of
[`$smwgSparqlDatabaseConnector`](https://www.semantic-mediawiki.org/wiki/Help:$smwgSparqlDatabaseConnector)
(#342) to avoid arbitrary class assignments in `$smwgSparqlDatabase` (now only used to assign custom
connectors).

Unit and integration tests were given extra focus together with a continuous integration of
[Jena Fuseki](http://jena.apache.org/) (1.0.2) (#337) and [Virtuoso opensource 6.1](https://github.com/openlink/virtuoso-opensource) (#394) to ensure that compatibility and functional
parity are going hand in hand with the rest of SMW. (Unfortunately `4Store` currently does not run
on the continuous integration platform, for details see [garlik#110](https://github.com/garlik/4store/issues/110)
but tests have been run successfully with a local `4store` instance).

At this moment, the only RDF store to be tested and to support [SPARQL 1.1](http://www.w3.org/TR/sparql11-query/)
is `Jena Fuseki` therefore other stores may not support all `query features`. For details to
the testing environment and its configuration, see the [README](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SPARQLStore/README.md)
file.

## Improved subobject support

Support for subobjects has been added to the RDF export. This new capability is used by the RDF
store functionality to also synchronize subobjects. (#344)

Subobjects now support sorting via the `@sortkey` annotation that stores an individual sortkey
per subobject. (#225)

## Notable bug fixes

* #279 Fixed undefined index in `DataTypeRegistry::getDefaultDataItemTypeId`
* #282 Output a message instead of an exception in `Special:WantedProperties` for unknown predefined properties
* #308 Fixed caching issue in `DataRebuilder` for duplicate title objects
* #312 Fixed fatal error in `CategoryResultPrinter` for when a mainlabel is hidden
* #322 Fixed file names containing spaces or non-ASCII characters for for downloadable result formats (csv, excel)
* #379 Modernized `dumpRDF.php` while deprecating the use of `SMW_dumpRDF.php` (bug 35679)
* #425 Deprecated `SMW_setup.php` in favour of `setupStore.php`
* #444 Fixed language namespace alias issue
* #420 Extended `ContentParser` to mitigate issues caused by the 62856 bug in MW 1.24+
* #405 Added a compatibility fix to mitigate issues caused by the `RefreshLinksJob` in MW 1.23+

### SPARQLStore

- #291 Fixed call to undefined method in `SPARQLStore`
- #338 Fixed exception in `ResultParser` for an invalid datatype (bug 62218)
- #385 Fixed '#' encoding for subobjects in `SMWExporter::findDataItemForExpElement` to enable `SPARQLStore` result display
- #387 Fixed `SPARQLStore` namespace query support (e.g `[[:+]]` )
- #415 Fixed `SPARQLStore` usage for `rebuildConceptCache.php` and `rebuildPropertyStatistics.php`
- #460 Fixed `SPARQLStore` subobject sub query and pre-defined property query support

## Behind the scenes

SMW 2.0 continues to convert its classes to use PHP namespaces in order to separate responsibilities
(#398, #404, #407, #409, #410, #411, #412, #416, #417, #418, #419, #421) and to be able to support
[PSR-4](http://www.php-fig.org/psr/psr-4/) in future.

* All `job` related classes of been moved to `SMW\MediaWiki\Jobs`
* All `hook` related classes of been moved to `SMW\MediaWiki\Hooks`
* All `api` related classes of been moved to `SMW\MediaWiki\Api`
* All `SPARQLStore` related classes now reside in `SMW\SPARQLStore`
* `SMWSparqlStore` and `SMWSparqlDatabase` where moved into the `SMW\SPARQLStore` namespace

Other internal enhancements or changes include:

* #278 Changed the `PropertyStatisticsTable` interface
* #289 Added [`CONTRIBUTING.md`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/CONTRIBUTING.md) for better contributor guidance
* #307 Added `removeDuplicates` option to `UpdateJob`
* #310 Fixed autoloading for `QueryPrinterRegistryTestCase`
* #311 Removed `MediaWikiTestCase` dependency
* #315 Updated jquery.qTip2 from v2.0.0 to v2.2.0 (Mar 17 2014)
* #332 Added the number of pages and percentage done to report messages when rebuilding selected pages
* #366 Extended `Sql3StubSemanticData` to load suobjects on request and introduced a `__sob` datatype for subobjects
* #382 Extended interface to support `format=count` information in `QueryResult`
* #453 Added [`COMPATIBILITY.md`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/COMPATIBILITY.md) for better user guidance

Deprecated classes or scripts:

* `SMW_conceptCache.php`
* `SMW_dumpRDF.php`
* `SMW_refreshData.php`
* `SMW_setup.php`
* `SMWSparqlStore`
* `SMWSparqlDatabase`
* `SMWIResultPrinter`

Removed classes or scripts:

* `SMWParseData`
