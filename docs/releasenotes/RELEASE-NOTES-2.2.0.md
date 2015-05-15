# Semantic MediaWiki 2.2

Released on May 9th, 2015.

## New features

* #770 Added the `--no-cache` option to `rebuildData.php` and the `--debug` option to `rebuildData.php` and `rebuildConceptCache.php` (refs #749, #766)
* #756 Added template support to the `#set` parser function
* #783 Added support for `wgCategoryCollation` setting in `CategoryResultPrinter` (#699, T40853)
* #829 Added the `--report-runtime` option to `rebuildConceptCache.php`
* #886 Extended rules on how an `ImportValue` need to contain a valid type definition
* #891 Enforced strict type declaration for properties with import references (type of the imported reference takes precedence over any user-defined type declaration)
* #892 Added support for `<swivt:file>` in order for a RDF export to point to a "real" file resource
* #893 Added information about "improper value for" to the statistics overview and as `API` parameter (`&errorcount`)
* #913 Fixed exception for usage of `Has subobject` as free annotation
* #914 Added restriction for "manual" annotation use to some pre-defined properties (e.g. `Has subobject`, `Has query`)
* #974 Added `named args` as parameter to `CategoryResultPrinter`
* #988 Added `sep` as parameter to the `TableResultPrinter` to define a separator for cell values

## Enhancements

* #958 Replaced defunct SMWRegsitry wiki registration on Special:SMWAdmin with a link to WikiApiary
* #976 Reduced the number of pages on which SMW JavaScript and CSS is loaded
* #994, #995 Made small performance improvements

## Bug fixes

* #556 Fixed Missing parentheses in `SQL` for disjunctions when temporary tables are not used by the `SQLStore`
* #764 Fixed DB error when a `#ask` query contains `order=random` for a `sqlite` or `postgres` DB platform (disabled `smwgQRandSortingSupport` for `postgres`)
* #860 Fixed escape character usage in `SPARQLStore`, `SQLStore`
* #860 Fixed handling of an empty result send by the `SPARQLStore` Sesame connector
* #861 Fixed owl property export declaration
* #863 Fixed missing interwiki encoding for the RDF export
* #864 Fixed empty searchlabel raw wikitext display for a `QueryResultPrinter` with `limit=0`
* #869 Fixed data update when moving a page to a non-enabled semantic namespace
* #870 Fixed `#set` where an error from a previous parse call could have affected succeeding `#set` error messages
* #882 Fixed exception in `SMWExportController` caused by an empty property reference
* #884 Fixed fetching of import vocabularies to be language independent
* #887 Fixed `pre` element rendering for the template "furtherresult" output link
* #896 Fixed empty caption for `false` when using the`#x` format option
* #928 Fixed exception in `SMWExportController` for use of `&stats` option
* #945 Fixed nesting of self-referenced concepts in a concept query
* #974 Fixed inconsistency when using `headers=plain` in `CategoryResultPrinter` also fixed the `__NOTOC__` hack

## Internal changes

* #373 Update `jquery.jstorage.js` (0.3.2 => 0.4.12)
* #494 Changes to the `SQLStore\QueryEngine` interface
* #711 Fetching annotations made by an `#ask` transcluded template
* #725 Moved psr-4 complaint classes into the top level 'src' folder
* #740 Added `serialization/serialization:~3.2` component dependency
* #771 Added `doctrine/dbal:~2.5` component dependency
* #772 Added `onoi/message-reporter:~1.0` component dependency
* #777 Moved all concept related code into a separate `ConceptCache` class
* #831 Added `onoi/cache:~1.0` component dependency
* #884, #983 Added `ControlledVocabularyImportContentFetcher`, `ImportValueParser` to isolate import vocabulary parsing from the `ImportValue` object
* #883 Added `CircularReferenceGuard` to monitor possible self/circular references in ask templates
* #914 Added `DIProperty::isUnrestrictedForUse`
* #924 Added `onoi/event-dispatcher:~1.0` component dependency
* #929 Added serialization to `ExpElement` objects
* #950 Renamed `PropertyTypeDiffFinder` to `PropertySpecDiffFinder`
* #951 Extended `UpdateDispatcherJob` to create `UpdateJob` chunks to avoid memory exhaustion
* #959 Added `ExpDataSerializer` and `ExpDataDeserializer`
* #984 Moved remaining part of `SMWSQLStore3QueryEngine` to `SMW\SQLStore\QueryEngine\QueryEngine`
