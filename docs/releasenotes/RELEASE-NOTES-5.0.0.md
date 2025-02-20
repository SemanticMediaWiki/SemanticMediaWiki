# Semantic MediaWiki 5.0.0

Released on TBD.

## Summary

This release mainly brings support for recent versions of MediaWiki and PHP.
Anyone using MediaWiki 1.41 or above, or PHP 8.1 or above, is recommended to upgrade.

## Compatibility

* Improved compatibility with MediaWiki 1.42
* Improved compatibility with MediaWiki 1.43
* Improved compatibility with PHP 8.1 and above
* Dropped support for MediaWiki older than 1.39
* Dropped support for PHP older than 8.1

For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Highlights

### User interface changes

Some user interface changes are deployed to make user facing front-end components more intutive and mobile-friendly by using [Codex](https://doc.wikimedia.org/codex/main/) from Wikimedia Foundation:

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

## New features and enhancements

* Allow RDF link in the head element to be disabled ([#5776](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5776))

## Breaking changes

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
* SMWElasticStore

SMWSearch alias was kept.

## Upgrading

Be advised that the [SMWSearch](https://www.semantic-mediawiki.org/wiki/Help:SMWSearch) feature (and so the [SEARCH_FORM_SCHEMA](https://www.semantic-mediawiki.org/wiki/Help:Schema/Type/SEARCH_FORM_SCHEMA) feature) is not working yet. See issue [#5782](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5782). If you use those features, change the MediaWiki [$wgSearchType](https://www.mediawiki.org/wiki/Manual:$wgSearchType) parameter to something other than `SMWSearch`. 

There is no need to run the "update.php" maintenance script or any of the rebuild data scripts (but it is still advisable to do so in order to make [table_optimizations](https://www.semantic-mediawiki.org/wiki/Database/Table_optimization) on the database).


## Contributors

* translatewiki.net
* paladox
* alistair3149
* Marko Ilic ([gesinn.it](https://gesinn.it))
* Sébastien Beyou
* Alexander Gesinn ([gesinn.it](https://gesinn.it))
* Jeroen De Dauw ([Professional Wiki](https://professional.wiki/))
* Karsten Hoffmeyer ([Professional Wiki](https://professional.wiki/))
* Robert Vogel
* Simon Stier
* Yvar
* Alexander Mashin
* Ferdinand Bachmann
* Youri vd Bogert
* dependabot[bot]
* thomas-topway-it
* jaideraf
