# Semantic MediaWiki 7.2.0

Released on TBD.

This is a [minor release](../RELEASE-POLICY.md). Thus, it contains no breaking changes, only new features and fixes.

Like SMW 7.1.0, this version is compatible with MediaWiki 1.43 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## New features and enhancements

* Added the `+display` option to the `#set` parser function, so a value can be stored and shown in one call without inline annotation syntax. The option follows a property assignment like `+sep` does and shows the values stored for that property, rendered the way the corresponding inline annotation would: `+display` or `+display=link` produce the linked, formatted value, `+display=text` the formatted value without a link ([#5624](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5624))
* Added `SMW\MediaWiki\Outputs::requireJsConfigVar()` so extensions can register JavaScript configuration variables through the SMW output mechanism, alongside modules, styles and head items, instead of emitting inline scripts ([#7028](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7028))
* Added the `{{#property_link:}}` parser function for linking to a property page from running text: `{{#property_link:Foo}}` and `{{#property_link:Foo|custom label}}` render the same output as the `[[Foo::@@@]]` and `[[Foo::@@@|custom label]]` annotation syntax, providing a dedicated syntax that does not rely on intercepting `[[…]]` links ([#5624](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5624), [#1855](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1855))

## Bug fixes

* Fixed a query showing incorrect printout values when the same property is requested through different printout contexts, such as a direct and an inverse printout or the same property with different sort options ([#7026](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7026))
* Fixed maintenance scripts run with `--auto-recovery` (such as `rebuildData` and `rebuildElasticIndex`) aborting with a `FileNotWritableException` on deployments where the extension directory is not writable; the recovery checkpoint is now stored in the database instead of a `.smw.json` file, so it no longer depends on a writable `$smwgConfigFileDir` ([#7030](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7030))
* Fixed viewing an old revision failing with a "This ParserOutput contains no text!" error on wikis running an extension that adds parser output metadata to the page, such as PageNotice ([#7033](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7033))
* Fixed `Special:Ask` returning an internal error when a query is run with `format=debug` ([#7035](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7035))
* Fixed long-running maintenance scripts (such as `rebuildData`, `rebuildFulltextSearchTable` and `dumpRDF`) exhausting memory on large wikis; MediaWiki's buffered stats samples, which accumulate in process memory for every processed entity and have no flush point on the command line, are now flushed periodically during the rebuild loops ([#7036](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7036))

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.2.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
