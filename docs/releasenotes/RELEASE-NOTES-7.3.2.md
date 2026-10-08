# Semantic MediaWiki 7.3.2

Released on TBD.

This is a [patch release](../RELEASE-POLICY.md). Thus, it contains only bug fixes, no new features, and no breaking changes.

Like SMW 7.3.1, this version is compatible with MediaWiki 1.43 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Changes

* Fixed flag settings such as `$smwgDVFeatures` or `$smwgFactboxFeatures` being unable to switch off a default flag. A flag list set in `LocalSettings.php` was merged with the default list instead of replacing it, so every default flag stayed enabled. If you relied on this to add a single flag (e.g. `$smwgDVFeatures[] = 'wpv-pipetrick';`), list the default flags you want to keep as well
* Fixed an `ArgumentCountError` in extensions that call `ListResultBuilder::getResultText()` without an output mode, such as the listwidget format of Semantic Result Formats. The parameter introduced in 7.3.1 now defaults to `SMW_OUTPUT_HTML`
* Fixed the ElasticStore taking OpenSearch for Elasticsearch 7.10.2 when OpenSearch runs with `compatibility.override_main_response_version` enabled, as CirrusSearch on MediaWiki 1.43 requires. Special:Version now names OpenSearch and its real version

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.3.2`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
