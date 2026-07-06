# Semantic MediaWiki 6.0.1

Released on August 26th, 2025.

This is a [patch release](../RELEASE-POLICY.md). Thus, it contains only bug fixes, no new features, and no breaking changes.

Like SMW 6.0.0, this version is compatible with MediaWiki 1.43 up to 1.44 and PHP 8.1 up to 8.4.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Changes

* Fixed fatal error when using Factbox in some scenarios ("[Call to a member function resetParseStartTime() on null](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6204)")
* Fixed MediaWiki 1.44 compatibility issues:
    * [FeedItem import error in FeedExportPrinter](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6205)
    * [Title import error when indexing Elasticsearch](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6215)
* Fixed error "[$mOutput must not be accessed before initialization](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6120)"

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 6.0.0, ensure the SMW version in `composer.local.json` is `^6.0.1`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
