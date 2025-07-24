# Semantic MediaWiki 5.1.0

Not a release yet.

This is a minor feature release.

Like SMW 5.0.0, this version is compatible with MediaWiki 1.39 up to 1.43 and PHP 8.1 up to 8.4.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

SMW 5.1.0 brings two new configuration settings and enhances API outputs by including inverse properties.
While this version also improves compatibility with MediaWiki 1.44, various issues remain, so this version
is known to not yet support MediaWiki 1.44. We recommend SMW users stay on MediaWiki 1.43 LTS for now.

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 5.0.0, ensure the SMW version in `composer.local.json` is `^5.1.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory

## Changes

* Added `smwgSetParserCacheTimestamp` setting to allow disabling invalidation of the parser cache (by [Professional Wiki])
* Added `smwgSetParserCacheKeys` setting to give control over how the parser cache key is built (by [Professional Wiki])
* Added support for serializing inverse (incoming) properties in API output. The API output now contains a `direction` key (`direct` or `inverse`) for each property (by Gesinn.it)

[Professional Wiki]: https://professional.wiki
