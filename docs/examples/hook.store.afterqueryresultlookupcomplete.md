This document contains examples on how the [`SMW::Store::AfterQueryResultLookupComplete`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.store.afterqueryresultlookupcomplete.md) hook can be used.

### Match unknown entities

Demonstrates how the `SMW::Store::AfterQueryResultLookupComplete` hook can be used to add unknown entities (not matched by the `QueryEngine`) derived from the query description (see [#3934][issue-3934]).

```php
use Hooks;
use SMW\Store;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\ValueDescription;
use SMWQueryResult as QueryResult;

Hooks::register( 'SMW::Store::AfterQueryResultLookupComplete', function( Store $store, QueryResult &$queryResult ) {

	// Contains matched results from the `QueryEngine`
	$results = $queryResult->getResults();
	$map = [];

	// Build a hash map to quickly perform a search on members of the
	// result set
	foreach ( $results as $result ) {
		$map[$result->getSha1()] = true;
	}

	// Inspect the query ...
	$query = $queryResult->getQuery();
	$description = $query->getDescription();

	// #3934
	// Check query pattern: [[Foo||Bar||Foobar]]
	if ( $description instanceof Disjunction ) {
		$descriptions = $description->getDescriptions();

		foreach ( $descriptions as $desc ) {
			if ( $desc instanceof ValueDescription ) {
				$dataItem = $desc->getDataItem();
				$sha1 = $dataItem->getSha1();

				// Already part of the result set?
				if ( isset( $map[$sha1]) ) {
					continue;
				}

				// Extend the result set!!, unordered
				$results[] = $dataItem;
				$map[$sha1] = true;
			}
		}
	}

	// Build a new query result object
	$queryResult = new QueryResult(
		$queryResult->getPrintRequests(),
		$query,
		$results,
		$store,
		$queryResult->hasFurtherResults()
	);

} );

```

## See also

- [query.description.md](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/code-snippets/query.description.md)

[issue-3934]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3934
[doc-about]: # (Examples implementing the `SMW::Store::AfterQueryResultLookupComplete`)
