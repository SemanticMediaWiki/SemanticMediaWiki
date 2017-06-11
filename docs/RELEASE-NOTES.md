# Semantic MediaWiki 3.0

This is not a release yet and is planned to be available between Q4 2017 and Q1 2018.

## Highlights

Highlights for this release include ... (#2065)

## Upgrading

This release requires ...  (#2065, #2461)

## New features and enhancements

* [#2065](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2065) Added entity specific collation support using the [`$smwgEntityCollation`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEntityCollation) setting
* #2398 Added `#ask` and `#show` parser function support for `@deferred` output mode
* #2420 `TableResultPrinter` to support a datatable output
* #2435 Added filter and armor of invisible chars
* [#2432](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2432) Added check for `readOnly` mode
* [#2453](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2453) Changed the approach on how referenced properties during an article delete are generated to optimize the update dispatcher
* #2461 Improved performance for fetching incoming properties
* [#2471](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2471) Added [`$smwgUseCategoryRedirect`](https://www.semantic-mediawiki.org/wiki/Help:$smwgUseCategoryRedirect) setting to allow finding redirects on categories
* [#2476](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2476) Added [`$smwgQExpensiveThreshold`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQExpensiveThreshold) and [`$smwgQExpensiveExecutionLimit`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQExpensiveExecutionLimit) to count and restrict expensive `#ask` and `#show` functions on a per page basis
* #2485

## Bug fixes

...

## Internal changes

* #2482 Added `TransactionalDeferredCallableUpdate`
* #2491

## Contributors

...
