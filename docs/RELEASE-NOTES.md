# Semantic MediaWiki 2.1

This is not a release yet.

## New features

### Support for semantic queries in Special:Search

This release adds support for semantic queries run directly from MediaWiki's standard search. You can turn on this capability by setting `$wgSearchType` to ["SMWSearch"](https://semantic-mediawiki.org/wiki/Help:SMWSearch). The related configuration parameter [``$smwgFallbackSearchType``](https://semantic-mediawiki.org/wiki/Help:$smwgFallbackSearchType) allows specifying which search engine to fall back to in case "SMWSearch" returns no results. (#450, #496, #505)

### SPARQLStore improvements

The SPARQLStore now supports concepts. (#696) 

Regex support for Page and URL values has also been added. It is now thus possible to run queries like `[[Url::~http://*query=*]] OR [[Url::~*ccc*]]`. (#679)

Notable performance improvements as well as many other fixes have been made to the SPARQLStore support, which can be found in the bug fix list.

### Other new features

* #546 Concepts can now be nested (bug 15316) 
* #537 Modernized `Special:SearchByProperty` interface
* #613 Added `subobject` parameter to the `BrowseBySubject` API module and imporved resolving of circular redirects
* #620 Added `--page` as export option to the `dumpRDF.php` maintenance script
* #633 Made ouput decoding for uri's human readable (bug 35452)
* #643 Added `--runtime` option to `rebuildData.php`. It allows you to see how much time was spend and how much memory was used.
* #659 Added [``$smwgEnabledEditPageHelp``](https://semantic-mediawiki.org/wiki/Help:$smwgEnabledEditPageHelp) option that enables showing a contextual help text on the edit page
* #664 Enabled semicolon escaping for record-type values (`\;`) (bug T17732)
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
* #694 Fixed probable race condition for `SQLStore`(`postgres`) when creating temporary tables

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
* #668 Changed `SQLStore` `iw` table field specification from `VARCHAR(32) binary` to `VARBINARY(32)` 
