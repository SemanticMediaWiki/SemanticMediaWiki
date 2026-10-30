# Semantic MediaWiki 7.4.0

Released on TBD.

This is a [minor release](../RELEASE-POLICY.md). Thus, it contains no breaking changes, only new features and fixes.

Like SMW 7.3.1, this version is compatible with MediaWiki 1.43 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## New features and enhancements

* Added chunked and job queue rebuilds to `rebuildFulltextSearchTable.php`, so the full-text search index can be rebuilt on hosts that stop long-running scripts ([#7133](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7133))

## Bug fixes

* Fixed `Special:Version` listing the Elasticsearch server twice on wikis that also run CirrusSearch ([#7143](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7143))
* Fixed flag settings such as `$smwgDVFeatures` being unable to switch off a default flag. If you add a single flag, as in `$smwgDVFeatures[] = 'wpv-pipetrick';`, now also list the default flags you want to keep ([#7135](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7135))
* Fixed an `ArgumentCountError` since 7.3.1 in extensions such as the listwidget format of Semantic Result Formats ([#7137](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7137))
* Fixed OpenSearch being taken for Elasticsearch 7.10.2 when it runs in the compatibility mode that CirrusSearch on MediaWiki 1.43 requires ([#7142](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7142))
* Fixed page content jumping while the entity examiner checks a page; its indicator now shows only when a check finds an issue ([#7150](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7150))
* Fixed editing schemas with the Page Schemas extension, broken since 7.0.0 ([#7154](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7154))
* Fixed URL values containing a space linking to a different URL: `http://example.org/Foo bar` and `http://example.org/Foo%20bar` now link to `http://example.org/Foo%20bar`, not `http://example.org/Foo_bar`. `Foo bar` and `Foo_bar` are now different values, so a query for one no longer finds the other. Link text now shows an underscore as an underscore, not as a space ([#7139](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7139))
* Fixed the help links of the maintenance scripts on `Special:SemanticMediaWiki` ([#7151](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7151))
* Fixed pages with `#ask` queries being parsed twice for each user language or other parser cache variant first viewed after one of their query dependencies changed, on wikis with `$smwgEnabledQueryDependencyLinksStore` enabled ([#7155](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7155))
* Fixed a `DataItemHandlerException` stopping page rendering and `rebuildData.php` when a query prints a stored value that can no longer be read; such values are now left out of the result ([#7161](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7161))
* Fixed page views and maintenance scripts failing with a `TypeError` on MediaWiki 1.47, which removed `$wgCachePrefix` ([#7163](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7163))
* Fixed `rebuildFulltextSearchTable.php` running slower since 7.0.0: it made a cache lookup for every stored value, which is a database query on most wikis when run from the command line, and now makes one per property ([#7165](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7165))

## Upgrading

No need to run "update.php" or any other migration scripts.

However, if your wiki uses properties of type URL or Annotation URI, run `php maintenance/run.php SemanticMediaWiki:rebuildData` after upgrading. Until then, queries on values containing a space may return wrong results.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.4.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
