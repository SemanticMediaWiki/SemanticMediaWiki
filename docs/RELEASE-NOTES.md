# Semantic MediaWiki 2.1

This is not a release yet.

## Support for semantic queries in Special:Search
This release adds support for semantic queries (#450, #496, #505) to be used directly from MediaWiki's standard search. By setting `$wgSearchType` to ["SMWSearch"](https://semantic-mediawiki.org/wiki/Help:SMWSearch), the search is activated and together with a related configuration parameter [``$smwgFallbackSearchType``](https://semantic-mediawiki.org/wiki/Help:$smwgFallbackSearchType) it is assured that a default search engine is being used in case "SMWSearch" returns no results.

## New features

* #546 Enabled nested concepts (bug 44467) 
* #537 Modernized `Special:SearchByProperty` interface 

## Bug fixes

* #520 Fixed the `SPAPRQLStore` query selection for subobjects for namespace condition
* #543 Removes invalid category value links to `SearchByProperty` on `Special:Browse` (bug 33449)
* #537 Fixed parameter encoding in `Special:SearchByProperty` for hyphens and spaces (bug 16150)

## Internal changes

* #350 Passes all unit tests on `HHVM` 3.3+
* #486 Added support for `Jena Fuseki` 1.1.0
* #487 Added an internal cache to improve `SPARQLStore` redirect lookup performance
* #512, #521 Added benchmark tests for different components such as job-queue, maintenance script, queries etc.
* #523 Enforces a non-display of the Factbox for a `delete action` and re-enable the Factbox for an undeleted page
* #532 Added `UrlEncoder` to recognize all special characters when creating a manual link to `Special:Browse`
* #534 Added a value hash to `SQLStore::fetchSemanticData` to ensure that only distinct values are displayed
* #557 Added `SMW::Store::selectQueryResultBefore` and `SMW::Store::selectQueryResultAfter` hook
