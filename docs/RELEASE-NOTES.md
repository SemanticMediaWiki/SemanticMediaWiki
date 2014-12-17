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
* #633 Added ouput decoding for uri's to be human readable (bug 35452)
* #643 Added `--runtime` as reporting option for memory usage and runtime to `rebuildData.php`
* #659 Added [``$smwgEnabledEditPageHelp``](https://semantic-mediawiki.org/wiki/Help:$smwgEnabledEditPageHelp) to show a contextual help text on the edit page
* #664 Enabled `\;` usage to support semicolon escaping for record-type values (bug T17732)
* #672 Added `Special:Log` support for events enabled in `smwgLogEventTypes` 

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
* #619 Fixed exception in `dumpRDF.php` caused by resolving a subobject for a redirect 
* #622 Fixed cache id mismatch for redirects in `SQLStore`
* #622 Fixed exception for when a `null` is returned by `ExportController::getSemanticData`
* #627 Enhanced `SPARQLStore` XML result parser to support `Virtuoso` singelton response
* #618 Fixed subobject disjunctive/conjunctive subquery handling
* #628 Fixed named subobject encoding in the `Exporter` to support accented characters
* #630 Fixed browse link generation for wikipages in `Special:Browse`
* #638 Fixed the hard-coded upper bound for the offset option of an inline query by replacing it with configuration parameter [```$smwgQUpperbound```](https://semantic-mediawiki.org/wiki/Help:$smwgQUpperbound)
* #638 Fixed `postgres` temporary table generation issue (bug 34855, #455, #462)
* #640 Fixed `QueryProcessor` to allow query conditions to contain `=` (bug 32955)
* #641 Removes service info links from the `Factbox`
* #654 Fixed broken field detection in record-type caused by html encoded strings (bug T23926)
* #656 Fixed `#REDIRECT` detection in MW 1.24+
* #661 Fixed regex search `(~/!)` for page-type property values (bug T36665, T49073, T33151, T35854)
* #674 Fixed regex search support for uri-type property values
* #683 Fixed invalid `:smw-redi` marker when `#REDIRECT` is removed manually 

## Internal changes

* #350 Passes all unit tests on `HHVM` 3.3+
* #486 Added support for `Jena Fuseki` 1.1.1
* #487, #576, #600 Added an internal cache to improve `SPARQLStore` redirect and export lookup performance
* #512, #521 Added benchmark tests for different components such as job-queue, maintenance script, queries etc.
* #523 Disabled the Factbox display for a `delete action` and re-enable the Factbox for an undeleted page
* #532 Added `UrlEncoder` to recognize all special characters when creating a manual link to `Special:Browse`
* #534 Added a value hash to `SQLStore::fetchSemanticData` to ensure that only distinct values are displayed
* #557 Added `SMW::Store::BeforeQueryResultLookupComplete` and `SMW::Store::AfterQueryResultLookupComplete` hook
* #590, #596 Added `CompoundConditionBuilder` and `ConditionBuilderStrategyFinder` to the `SPARQLStore`
* #645 Added `RedirectInfoStore` to isolate access to redirect information and cache info requests
* #646 Improved error message handling for the `_num` data type
* #665 Replaced arbitrary DB access in `Store::updateData` with `PageUpdater::doPurgeParserCache`
* #667 Added `Database::beginTransaction` and `Database::commitTransaction` 
* #670 Added `SMW::SQLStore::BeforeChangeTitleComplete` hook 
* #673 Extended `DataValueFactory` to ignore `$wgCapitalLinks` settings for the property namespace 
* #678 Added `PropertyRegistry` to remove global state from `DIProperty`
