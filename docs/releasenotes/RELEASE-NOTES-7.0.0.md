# Semantic MediaWiki 7.0.0

Released on TBD.

Like SMW 6.0.x, this version is compatible with MediaWiki 1.43 up to 1.45 and PHP 8.1 up to 8.4.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Changes

* Replaced the vendored `Onoi\Tesa` text sanitizer library with PHP `intl` built-ins for fulltext search text processing. Users with `smwgEnabledFulltextSearch` enabled must run `rebuildFulltextSearchTable.php` after upgrading. Transliteration now uses ICU instead of a static mapping table, which produces minor differences for some characters (e.g., German ü→u instead of ü→ue). This does not affect search match quality.
* Removed unused internal classes: `HtmlVTabs`, `SchemaParameterTypeMismatchException`, `CleanUpTables`, and `FlatSemanticDataSerializer`.
* Removed the `$smwgSparqlRepositoryConnectorForcedHttpVersion` setting. HTTP version negotiation is now handled by MediaWiki's HTTP layer. The `mediawiki/http-request` (`Onoi\HttpRequest`) dependency has been dropped — SPARQL store connectors and `RemoteRequest` now use MediaWiki core's `HttpRequestFactory`.

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 6.0.0, ensure the SMW version in `composer.local.json` is `^7.0.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
