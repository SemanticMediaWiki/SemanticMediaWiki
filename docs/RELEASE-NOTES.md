# Semantic MediaWiki 2.1

This is not a release yet.

## Support for semantic queries in Special:Search
This release adds support for semantic queries (#450, #496, #505) to be used directly from MediaWiki's standard search. By setting `$wgSearchType` to ["SMWSearch"](https://semantic-mediawiki.org/wiki/Help:SMWSearch), the search is activated and together with a related configuration parameter [``$smwgFallbackSearchType``](https://semantic-mediawiki.org/wiki/Help:$smwgFallbackSearchType) it is assured that a default search engine is being used in case "SMWSearch" returns no results.

## Internal changes

* #350 Passes all unit tests on `HHVM` 3.3+
* #486 Added support for `Jena Fuseki` 1.1.0
* #487 Added an internal cache to improve `SPARQLStore` redirect lookup performance 
* #512, #521 Added benchmark tests for different components such as jobqueue, maintenance script, queries etc.
* #523 Enforces a non-display of the Factbox for a `delete action` and enable the Factbox display for an undeleted page
