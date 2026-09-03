# Semantic MediaWiki 7.2.2

Released on TBD.

This is a [patch release](../RELEASE-POLICY.md). Thus, it contains only bug fixes, no new features, and no breaking changes.

Like SMW 7.2.1, this version is compatible with MediaWiki 1.43 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Changes

* Fixed wikis with the query result cache enabled (`$smwgQueryResultCacheType`) reading the object cache once per identical query on a page instead of once per page, and reporting no cache hits at all in the query cache statistics on `Special:SemanticMediaWiki` ([#7102](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7102))
* Fixed a failed data update or setup state write hiding its real cause when rolling the transaction back also failed ([#7107](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7107))
* Fixed the tooltip of `{{#info:}}` and `{{#property_link:}}` not working on `Special:ExpandTemplates` when a context title is given ([#7109](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7109))

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.2.2`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
