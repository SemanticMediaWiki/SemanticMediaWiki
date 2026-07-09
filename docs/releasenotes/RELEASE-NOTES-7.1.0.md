# Semantic MediaWiki 7.1.0

Released on July 9, 2026.

## Summary

This is a [minor release](../RELEASE-POLICY.md). Thus, it contains no breaking changes, only new features and fixes.

Like SMW 7.0.x, this version is compatible with MediaWiki 1.43 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.1.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory

## New features and enhancements

* Added the `optimizeStore.php` maintenance script to run storage backend table optimization on its own, independently of `setupStore.php`, with a `--with-maintenance-log` option ([#4696](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4696))
* Improved performance of category- and property-hierarchy queries by resolving the hierarchy in a single recursive query on MariaDB, MySQL 8+, PostgreSQL, and SQLite, avoiding the previous per-level round-trips and scratch temporary tables (MySQL 5.7 keeps the iterative path) ([#4527](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4527))
* `rebuildData` now reports the number of failed pages and exits with a non-zero status when errors are logged under `--ignore-exceptions`, instead of reporting `done` ([#6975](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6975))

## Bug fixes

* Fixed queries combining a concept with a single-page restriction (for example `[[Concept:Foo]] [[Bar]]`) returning pages that are not members of the concept ([#6994](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6994))
* Fixed the `smwgSetParserCacheTimestamp` feature overwriting a page's revision date with the current time; it now sets the parser cache time instead ([#6982](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6982))
* Fixed read-only requests on SQLite being recorded as having made primary database writes, caused by SMW's query temporary tables not being recognised as temporary by MediaWiki ([#6984](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6984))
* Fixed queries combining subqueries with `OR` (for example `[[Has color::Brown]] OR [[Has color::Black]]`) failing on SQLite and PostgreSQL with an `INSERT IGNORE` syntax error ([#6987](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6987))
* Fixed `#ask` queries with an `offset` being capped to a small number of results; deeper result pages now return correctly ([#6983](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6983))
* Fixed the `action=ask` API with `format=count` returning an empty result with `count: 0` instead of the actual result count ([#7006](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7006))
* Fixed `rebuildData` with `--ignore-exceptions` aborting the rest of the run and failing at shutdown with a database transaction error after the first page that errors during its update; the failing page is now rolled back and skipped so the run continues ([#6975](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6975))
* Fixed `Property` page subject lists appearing in an arbitrary order on MySQL 8, where the lookup relied on implicit `GROUP BY` ordering that MySQL 8 no longer provides ([#7002](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7002))
* Fixed non-image files (such as videos, audio, or PDFs) referenced through a property value not being recorded in the file's usage tracking, unlike images ([#6141](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6141))
* Fixed the MySQL `FORCE INDEX` hint for heavily-used Date property queries being silently dropped, restoring the intended query optimisation ([#6998](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6998))
* Fixed `Error 1205: Lock wait timeout exceeded` failures when answering category- or property-hierarchy queries (for example saving or viewing pages with `#ask` over subcategories) while edits or data rebuilds run concurrently; the subcategory/subproperty hierarchy is now resolved with a non-locking read instead of a locking one ([#4527](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4527))
* Fixed `Error 1205: Lock wait timeout exceeded` and `Error 1213: Deadlock found` failures when answering queries that combine subqueries with `OR` (for example `<q>[[Category:A]]</q> OR <q>[[Category:B]]</q>`) while edits or data rebuilds run concurrently; each `OR` branch is now materialised with a non-locking read instead of a locking one ([#7007](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7007))
* Fixed the filter form on property pages whose title contains a slash (for example `Property:Task/Desc`) reloading the wrong page, because the form's hidden title field was truncated at the first slash ([#6142](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6142))
* Fixed SPARQLStore queries that combine a fixed page with a bare not-like page comparison (for example `[[A]][[!~B]]`) wrongly returning no results ([#7017](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7017))
* Fixed DataTables-based `#ask` results loading indefinitely when a DynamicPageList query on the same page triggers a nested parse ([#7009](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7009))
* Fixed `[[Property::!+]]` queries (pages that have no value for a given property) being ignored on the SPARQLStore, which silently required the property to be present instead; such pages are now matched, including inside `OR` subqueries ([#7018](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7018), [#7021](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7021))
