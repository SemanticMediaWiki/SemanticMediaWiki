# Query dependency

Is a feature that tracks entity dependencies of embedded queries and allows them to be used to invalidate the [`parser cache`][parsercache] (via the `RejectParserCacheValue` hook) of subjects where it is assumed that outdated dependencies exist. The tracking provides a method for embedded queries to be re-parsed on a page view request to yield the latest available result set while keeping the `parser cache` generally available and intact.

## Requirements

- `smwgEnabledQueryDependencyLinksStore` to be enabled to track entities used in queries via the `SQLStore::QUERY_LINKS_TABLE` table
- `smwgQueryProfiler` to be enabled to track embedded queries

## Features and limitations

- Dependencies are resolved for properties, categories, concepts (non cached) and hierarchies
- Namespace queries, e.g. <code><nowiki>[[Help:+]]</nowiki></code> are not tracked (this would significantly impact update performance for when a single namespace dependency is altered)
- Queries with arbitrary conditions, e.g. <code><nowiki>[[~Issue/*]]</nowiki></code> cannot be tracked as they are not distinguishable in terms of an object description
- Queries with `limit` "<code>0</code>" (<code>|limit=0</code>) are not tracked (queries return an empty result list and only represent a simple link)
- Queries via `Special:Ask` are not tracked (those are not embedded)
- Invalidation of the parser cache happens on viewing pages
- Setting `smwgQueryDependencyPropertyExemptionList` contains property keys that are excluded from detection

## Technical notes

- src
  - SQLStore
    - QueryDependency
      - `DependencyLinksTableUpdater` responsible for updating the `SQLStore::QUERY_LINKS_TABLE` table
      - `DependencyLinksValidator` detect a possible discrepancy and is triggered via the `RejectParserCacheValue` hook
      - `QueryDependencyLinksStore` prune, find, and update dependencies
      - `QueryReferenceBacklinks` to display query back links via `Special:Browse`
      - `QueryResultDependencyListResolver` resolve entities used in a `QueryResult`

[parsercache]:https://www.mediawiki.org/wiki/Manual:$wgParserCacheType
