# Semantic MediaWiki 7.1.0

Released on TBD.

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

## Changes

* Fixed the `smwgSetParserCacheTimestamp` feature overwriting a page's revision date with the current time; it now sets the parser cache time instead ([#6982](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6982))
* Fixed read-only requests on SQLite being recorded as having made primary database writes, caused by SMW's query temporary tables not being recognised as temporary by MediaWiki ([#6984](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6984))
* Fixed queries combining subqueries with `OR` (for example `[[Has color::Brown]] OR [[Has color::Black]]`) failing on SQLite and PostgreSQL with an `INSERT IGNORE` syntax error ([#6987](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6987))
* Fixed `#ask` queries with an `offset` being capped to a small number of results; deeper result pages now return correctly ([#6983](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6983))
* `rebuildData` now reports the number of failed pages and exits with a non-zero status when errors are logged under `--ignore-exceptions`, instead of reporting `done` ([#6975](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6975))
* Fixed `rebuildData` with `--ignore-exceptions` aborting the rest of the run and failing at shutdown with a database transaction error after the first page that errors during its update; the failing page is now rolled back and skipped so the run continues ([#6975](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6975))
