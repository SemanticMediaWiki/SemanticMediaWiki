# Semantic MediaWiki 7.2.0

Released on TBD.

This is a [minor release](../RELEASE-POLICY.md). Thus, it contains no breaking changes, only new features and fixes.

Like SMW 7.1.0, this version is compatible with MediaWiki 1.43 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## New features and enhancements

* Added `SMW\MediaWiki\Outputs::requireJsConfigVar()` so extensions can register JavaScript configuration variables through the SMW output mechanism, alongside modules, styles and head items, instead of emitting inline scripts ([#7028](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7028))

## Bug fixes

* Fixed a query showing incorrect printout values when the same property is requested through different printout contexts, such as a direct and an inverse printout or the same property with different sort options ([#7026](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7026))

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.2.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
