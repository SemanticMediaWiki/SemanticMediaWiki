# Semantic MediaWiki 6.0.0

Released on August 12, 2025.

## Summary

This release mainly brings support for recent versions of MediaWiki.
Upgrading is recommended for anyone using MediaWiki 1.43 or later.

## Compatibility

* Added support for MediaWiki 1.43.2+ and 1.44
* Dropped support for MediaWiki older than 1.43

For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: ensure the SMW version in `composer.local.json` is `^6.0.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory

## Changes

* BREAKING CHANGE: Reverted "Support additional formatting options in ask queries"
* Use MediaWiki file backend for ingest (by Marijn van Wezel)
* Added footer icon spacing (by [Professional Wiki])
* Fixed compatability issues with MediaWiki 1.43.2+, 1.44 (by Paladox and [Professional Wiki])
* Fixed DB query issues such as `RuntimeException: Identifier must not contain quote, dot or null characters` (by huaj1ng)
* Fixed deprecation message when running `update.php` (by emwiemaikel)
* Translation update

[Professional Wiki]: https://professional.wiki