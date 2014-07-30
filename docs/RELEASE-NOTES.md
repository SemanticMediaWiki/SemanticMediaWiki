# Semantic MediaWiki 2.0 RC3

Third release candidate for SMW 2.0. Made available on 2014-07-23.

## Compatibility changes

Semantic MediaWiki 2.0 is compatible with MediaWiki 1.19 up to MediaWiki 1.23, and possibly later
versions. Support for both MediaWiki 1.23 and MediaWiki 1.24 was improved compared to SMW 1.9.

PHP compatibility remains the same as in SMW 1.9: all versions from PHP 5.3.2 to PHP 6.x.

For a full overview, see our [compatibility matrix](COMPATIBILITY.md).

## Improved SPARQLStore support

[SMW 1.6](http://semantic-mediawiki.org/wiki/SMW_1.6#Synchronizing_SMW_with_RDF_stores) introduced
support for data synchronization with RDF back-ends. SMW 2.0 extends the existing implementation and
adds an additional database connector for [Jena Fuseki](http://jena.apache.org/) 1.0.2 (#339). It
also restores the support for `4Store` 1.1.4  (#370, bug 43708, bug 44700). Other fixes include:

- #291 Fixed call to undefined method in `SPARQLStore`
- #338 Fixed exception in `ResultParser` for an invalid datatype (bug 62218)
- #385 Fixed '#' encoding for subobjects in `SMWExporter::findDataItemForExpElement` to enable `SPARQLStore` result display
- #387 Fixed `SPARQLStore` namespace query support (e.g `[[:+]]` )
- #415 Fixed `SPARQLStore` usage for `rebuildConceptCache.php` and `rebuildPropertyStatistics.php`
- #460 Fixed `SPARQLStore` subobject subqueries and pre-defined property queries support

The [`smwgSparqlDatabase`](https://semantic-mediawiki.org/wiki/Help:$smwgSparqlDatabase) setting
introduced in 1.6 has been deprecated in favour of
[`$smwgSparqlDatabaseConnector`](https://semantic-mediawiki.org/wiki/Help:$smwgSparqlDatabaseConnector)
(#342) to avoid arbitrary class assignments in `$smwgSparqlDatabase` (now only used to assign custom
connectors).

`SPARQLStore` has been converted to make use of a separate namespace together with improvements that
allow for better testability and code readability (#360, #371, #379, #375, #383, #392, #393, #395, #402, #403, #415).

The `SMWSparqlStore` and `SMWSparqlDatabase` class names are kept for legacy support but it is
suggested to use the new settings parameter.

Unit and integration tests were given extra focus together with a continuous integraton of  [Jena Fuseki](http://jena.apache.org/) (1.0.2) (#337) and [Virtuoso opensource 6.1]
(https://github.com/openlink/virtuoso-opensource) (#394) to ensure that compatibility and functional
parity are going hand in hand with the rest of SMW. (Unfortunately `4Store` currently does not run
on the continuous integration platform, for details see [garlik#110]
(https://github.com/garlik/4store/issues/110)but tests have been run successfully with a local
`4store` instance).

At this moment, the only RDF store to be tested and to support [SPARQL 1.1](http://www.w3.org/TR/sparql11-query/)
is `Jena Fuseki` therefore other stores may not support all `query features`. For details to
the testing environment and its configuration, see the [readme]
(https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/includes/src/SPARQLStore/README.md) file.

## Improved RDF subobject support

Previous releases came without RDF subobject support but this has been corrected in 2.0. The RDF
subobject export (#377, bug 48708) and RDF store subobject synchronization (#378, bug 48361) are
fixed and come with appropriate test coverage in order to make the `SQLStore` and the `SPARQLStore`
equally supported.

Subobject do now support a sort annotation by using the `@sortkey` identifier (#225) that stores an
individual sortkey per subobject.

## Quality and stability improvements

A great deal of effort has been put into ensuring both existing and new features work well.
Not just at present, but also in future releases. And not just with MySQL and one version of
MediaWiki, but on all platforms we support. This dedication to quality has resulted in many
bugs being discovered and fixed, and makes future regressions much less likely.

Continuous integration is now an integral part of the development process behind SMW. As of
the 2.0 release, SMW has over 2300 automated tests, which cover two thirds of the codebase.
These tests are run automatically for every change made to the code, on machines with different
databases, different versions of PHP, different SPARQL stores and different versions of MediaWiki.

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

## Behind the scenes

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
* #366 Extended `Sql3StubSemanticData` to load suobjects on request and introduced a `__sob` datatype for subobjects
* #382 Extended interface to support `format=count` information in `QueryResult`
* #453 Added [`COMPATIBILITY.md`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/COMPATIBILITY.md) for better user guidance

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
