# Semantic MediaWiki 5.0.0

Released on March 10, 2025.

## Summary

This release mainly brings support for recent versions of MediaWiki and PHP.
Upgrading is recommended for anyone using MediaWiki 1.41 or later.

## Compatibility

* Added support for MediaWiki 1.42 and 1.43
* Dropped support for MediaWiki older than 1.39
* Improved compatibility with PHP 8.3 and above
* Dropped support for PHP older than 8.1

For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Highlights

### User Interface Changes

Some user interface changes are deployed to make user-facing front-end components more intuitive and
mobile-friendly by using [Codex](https://doc.wikimedia.org/codex/main/) from Wikimedia Foundation:

* Start using Codex Design tokens and improve various styles ([#5786](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5786))
* Rewrite Special:Browse and its factbox ([#5788](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5788))
* Style SMW tabs similar to Codex ([#5997](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5997))
* Use new Factbox component at the bottom of the page  ([#5804](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5804))
* Minor visual improvement to Factbox ([#5845](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5845))
* Minor cleanups on tab styles ([#5991](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5991))
* Use semantically correct heading and drop custom heading styles ([#5992](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5992))

### Performance

* Use SVGs for logos ([#5756](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5756))
* Convert base64 images into actual files ([#5761](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5761))
* Clean up tooltip-related ResourceLoader modules ([#5762](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5762))
* Minor clean up to SMW Tippy styles ([#5769](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5769))
* Clean up single-use ResourceLoader modules ([#5777](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5777))

## New Features and Enhancements

* Support additional formatting options on the `table`/`broadtable` result format (`|+width=`, `|+height=`, `|+link=` and `|+thclass=`) ([#5739](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5739))
* Allow RDF link in the head element to be disabled ([#5776](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5776))
* Update Schema.org vocabulary from version 14 to 28 ([Commit cc5a1db](https://github.com/SemanticMediaWiki/SemanticMediaWiki/commit/cc5a1db96f78d5509950707c20648aa20e524481)), fix in Skos vocabulary ([Commit 7740dd6](https://github.com/SemanticMediaWiki/SemanticMediaWiki/commit/7740dd615f4063607b0e6121641ad853160b9c30))

## Breaking Changes

- [#6021](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6021) ChangePropagationDispatchJob: Don't presume job will be run on same server

  The param 'dataFile' and 'checkSum' have been dropped in ChangePropagationDispatchJob. No longer is a temp file created, instead the contents is supplied
  in the 'data' param.

- [#6044](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6044) Remove deprecated class alias

The following class aliases were removed as they were deprecated:

* \SMW\Localizer
* \SMW\Message
* \SMW\Lang\Lang
* \SMWSerializer
* \SMWTurtleSerializer
* \SMWRDFXMLSerializer
* SMWRDFResultPrinter
* SMWEmbeddedResultPrinter
* SMWDSVResultPrinter
* SMWAggregatablePrinter
* SMW\PropertyAnnotator
* SMW\PropertySpecificationLookup
* SMW\PropertyRestrictionExaminer
* SMWResultArray
* SMWQueryResult
* \SMW\ApplicationFactory
* \SMWSql3SmwIds
* SMW\DeferredCallableUpdate
* SMW\DeferredTransactionalCallableUpdate
* SMW\InTextAnnotationParser
* SMW\UrlEncoder
* SMW\QueryResultPrinter
* SMWIResultPrinter
* SMW\ExportPrinter
* SMW\ResultPrinter
* SMWResultPrinter
* SMW\FileExportPrinter
* SMW\ListResultPrinter
* SMWQueryParser
* SMW\SQLStore\CompositePropertyTableDiffIterator
* SMW\DBConnectionProvider
* SMWPropertyValue
* SMWStringValue
* \SMW\MediaWiki\Database
* SMWDIString
* SMWStore
* SMWUpdateJob
* SMWRefreshJob
* SMWSemanticData
* SMWDIWikiPage
* SMWDIProperty
* SMWDISerializer
* SMWDataValueFactory
* SMWDataItemException
* SMWSQLStore3Table
* SMWDIConcept
* SMWTableResultPrinter
* SMWExportPrinter
* SMWCategoryResultPrinter
* SMWListResultPrinter
* SMWSparqlStore
* SMWSparqlDatabase4Store
* SMWSparqlDatabaseVirtuoso
* SMWSparqlDatabase
* SMWSQLStore3
* SMWDescription
* SMWThingDescription
* SMWClassDescription
* SMWConceptDescription
* SMWNamespaceDescription
* SMWValueDescription
* SMWConjunction
* SMWDisjunction
* SMWSomeProperty
* SMWPrintRequest
* SMW\SQLStore\PropertiesCollector
* SMW\SQLStore\UnusedPropertiesCollector
* SMWExpElement
* SMWExpResource
* SMWExpNsResource
* SMWExpLiteral
* SMWSQLStore3QueryEngine
* SMW\ParserParameterFormatter
* SMW\ParameterFormatterFactory
* SMWRequestOptions
* SMWStringCondition
* SMW\Hash
* SMWBoolValue
* SMW\FormatFactory
* SMW\SubobjectParserFunction
* SMW\RecurringEventsParserFunction
* SMW\SQLStore\TableDefinition
* SMWContainerSemanticData

SMWSearch alias was kept.

## Upgrading

Be advised that the [SMWSearch](https://www.semantic-mediawiki.org/wiki/Help:SMWSearch) feature (and so the [SEARCH_FORM_SCHEMA](https://www.semantic-mediawiki.org/wiki/Help:Schema/Type/SEARCH_FORM_SCHEMA) feature) is not working yet. See issue [#5782](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5782). If you use those features, change the MediaWiki [$wgSearchType](https://www.mediawiki.org/wiki/Manual:$wgSearchType) parameter to something other than `SMWSearch`.

If you use the [ElasticStore](https://www.semantic-mediawiki.org/wiki/Help:ElasticStore) or the [SPARQLStore](https://www.semantic-mediawiki.org/wiki/Help:SPARQLStore) feature, make sure you have the `$smwgDefaultStore` set to `SMW\Elastic\ElasticStore` or `SMW\SPARQLStore\SPARQLStore` (the aliases `SMWElasticStore` and `SMWSparqlStore` were removed).

There is no need to run the "update.php" maintenance script or any of the rebuild data scripts (but it is still advisable to do so to make [table optimizations](https://www.semantic-mediawiki.org/wiki/Database/Table_optimization) on the database).

## Contributors

* translatewiki.net
* paladox
* alistair3149 ([Professional Wiki](https://professional.wiki/))
* Marko Ilic ([gesinn.it](https://gesinn.it))
* Sébastien Beyou ([Wiki Valley](https://wiki-valley.com))
* Alexander Gesinn ([gesinn.it](https://gesinn.it))
* Jeroen De Dauw ([Professional Wiki](https://professional.wiki/))
* Karsten Hoffmeyer ([Professional Wiki](https://professional.wiki/))
* Robert Vogel ([Hallo Welt!](https://hallowelt.com/))
* Simon Stier
* Yvar ([ArchiXL](https://www.archixl.nl))
* Alexander Mashin
* Ferdinand Bachmann
* Youri vd Bogert ([ArchiXL](https://www.archixl.nl))
* dependabot[bot]
* thomas-topway-it ([KM-A](https://km-a.net/))
* jaideraf
* Bernhard Krabina ([KM-A](https://km-a.net/))

## See Also

* [Semantic MediaWiki 5 Released](https://professional.wiki/en/news/semantic-mediawiki-5-released) blog post
