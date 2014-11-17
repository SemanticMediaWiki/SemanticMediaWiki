# Semantic MediaWiki 2.1

This is not a release yet.

## New features

### Support for semantic queries in Special:Search
This release adds support for semantic queries (#450, #496, #505) to be used directly from MediaWiki's standard search. By setting `$wgSearchType` to ["SMWSearch"](https://semantic-mediawiki.org/wiki/Help:SMWSearch), the search is activated and together with a related configuration parameter [``$smwgFallbackSearchType``](https://semantic-mediawiki.org/wiki/Help:$smwgFallbackSearchType) it is assured that a default search engine is being used in case "SMWSearch" returns no results.

### Other new features

* #546 Enabled nested concepts (bug 44467) 
* #537 Modernized `Special:SearchByProperty` interface
* #613 Added the `subobject` parameter to the `BrowseBySubject` API module and prevent to resolve circular redirects during an API request using `DeepRedirectTargetResolver`
* #620 Added `--page` as export option to `dumpRDF.php` 

## Bug fixes

* #500 Fixed the `SPAPRQLStore` to return a `FalseCondition` instead of an exception for not supported data types (e.g `Geo`)
* #520 Fixed the `SPAPRQLStore` query selection for subobjects used with a namespace condition
* #543 Removes invalid category value links to `SearchByProperty` on `Special:Browse` (bug 33449)
* #537 Fixed parameter encoding in `Special:SearchByProperty` for hyphens and spaces (bug 16150)
* #554 Enhanced concept pages to provide time and date of the last update
* #566 Fixed the `SPARQLStore` query result display for moved pages (a.k.a. "gost" pages)
* #601 Fixed movability for predefined property pages
* #615 Fixed data display inconsistency for pre-existing redirects 
* #617 Fixed circular `UpdateJob` caused by redirects
* #627 Enhanced `SPARQLStore` XML result parser to support `Virtuoso` singelton response
* #618 Fixed subobject disjunctive/conjunctive subquery handling

## Internal changes

* #350 Passes all unit tests on `HHVM` 3.3+
* #486 Added support for `Jena Fuseki` 1.1.0
* #487, #576, #600 Added an internal cache to improve `SPARQLStore` redirect and export lookup performance
* #512, #521 Added benchmark tests for different components such as job-queue, maintenance script, queries etc.
* #523 Disabled the Factbox display for a `delete action` and re-enable the Factbox for an undeleted page
* #532 Added `UrlEncoder` to recognize all special characters when creating a manual link to `Special:Browse`
* #534 Added a value hash to `SQLStore::fetchSemanticData` to ensure that only distinct values are displayed
* #557 Added `SMW::Store::BeforeQueryResultLookupComplete` and `SMW::Store::AfterQueryResultLookupComplete` hook
* #590, #596 Added `CompoundConditionBuilder` and `ConditionBuilderStrategyFinder` to the `SPARQLStore`
