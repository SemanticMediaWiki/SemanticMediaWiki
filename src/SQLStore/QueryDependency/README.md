# Query dependency

The query dependencies tracking provides a method to link subjects to embedded queries and to validate the "freshness" of a query relative to its registered dependencies. In cases where the freshness characteristic can no longer be attributed it will signal to the `RejectParserCacheValue` hook to evict the [`parser cache`][parsercache], allowing a page view request to re-parse its content hereby updating query results while otherwise keeping the `parser cache` intact and untouched when queries are assumed to be fresh.

## Requirements

- [`$smwgEnabledQueryDependencyLinksStore`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEnabledQueryDependencyLinksStore) to be enabled to track entities used in queries via the `SQLStore::QUERY_LINKS_TABLE` table
- [`$smwgQueryProfiler`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQueryProfiler) to be enabled to track embedded queries

## Features and limitations

- Dependencies are resolved for properties, categories, concepts (non cached) and hierarchies
- Invalidation of the parser cache happens on a view request (not in advance of any viewing)
- Setting [`$smwgQueryDependencyPropertyExemptionList`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQueryDependencyPropertyExemptionList) contains property keys that are excluded from detection because their update frequency may cause a disruption or have been categorized as unnecessary for a tracking

### Exemption rules

- Namespace queries, e.g. <code><nowiki>[[Help:+]]</nowiki></code> are not tracked (this would significantly impact update performance for when a single namespace dependency is altered)
- Queries with arbitrary conditions, e.g. <code><nowiki>[[~Issue/*]]</nowiki></code> cannot be tracked as they are not distinguishable in terms of an object description (cannot be assigned an ID which is required for a successful tracking)
- Queries with `limit` "<code>0</code>" (<code>|limit=0</code>) are not tracked (queries return an empty result list and only represent a simple link)
- Queries via `Special:Ask` are not tracked (those are not embedded)

## Technical notes

- SQLStore
  - QueryDependency
    - `DependencyLinksTableUpdater` responsible for updating the `SQLStore::QUERY_LINKS_TABLE` table
    - `DependencyLinksValidator` detect a possible discrepancy (validate the "freshness") and is triggered via the `RejectParserCacheValue` hook
    - `QueryDependencyLinksStore` prune, find, and update dependencies
    - `QueryReferenceBacklinks` to display query back links via `Special:Browse`
    - `QueryResultDependencyListResolver` resolve entities used in a `QueryResult`

[parsercache]:https://www.mediawiki.org/wiki/Manual:$wgParserCacheType
