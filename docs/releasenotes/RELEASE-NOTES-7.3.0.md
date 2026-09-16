# Semantic MediaWiki 7.3.0

Released on TBD.

This is a [minor release](../RELEASE-POLICY.md). Thus, it contains no breaking changes, only new features and fixes.

Like SMW 7.2.1, this version is compatible with MediaWiki 1.43 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## New features and enhancements

* Full-text search now keeps a term that carries a wildcard even when it is shorter than `$smwgFulltextSearchMinTokenSize` or is a stop word, matching how MySQL and MariaDB treat the truncation operator. A term such as `[[Has text::~to* be]]` previously searched only for "be" and now also searches for everything starting with "to", so such queries can return more results than before.
* Added the `$smwgConfigProfiles` setting for applying the configuration profiles in `data/config`, for example `$smwgConfigProfiles = [ 'db-primary-keys' ];`. The `require` in `LocalSettings.php` recommended by the 7.0.0 release notes did not apply these profiles. Replace it with the setting, since the wiki does not start when a profile is loaded both ways ([#7106](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7106))

## Bug fixes

* Fixed wikis with the query result cache enabled (`$smwgQueryResultCacheType`) reading the object cache once per identical query on a page instead of once per page, and reporting no cache hits at all in the query cache statistics on `Special:SemanticMediaWiki` ([#7102](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7102))
* Fixed a failed data update or setup state write hiding its real cause when rolling the transaction back also failed ([#7107](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7107))
* Fixed the tooltip of `{{#info:}}` and `{{#property_link:}}` not working on `Special:ExpandTemplates` when a context title is given ([#7109](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7109))
* Fixed links and other markup in a property description not being rendered in the property tooltip ([#5494](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5494))
* Fixed full-text search queries failing with a database error when the search term left a boolean operator without a term, such as `[[Has text::~O'Se*]]`, where both parts of the term are too short to be indexed and only the wildcard remained ([#6129](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6129))
* Fixed full-text search queries failing with a database error when the search term contained an at sign, as in `[[Has text::~name@example.org]]`
* Fixed percent-encoded characters in URL values, such as `%2F` in `https://example.org/a%2Fb`, being decoded when stored, which turned the URL into a different, broken one ([#5212](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5212))
* Fixed the ask API and result formats such as `datatables` failing with a fatal error on a printout that chains to a record or monolingual text property, such as `|?Page.Name` ([#5713](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5713))
* Fixed `|+lang` and `|+index` having no effect on a printout that chains to a record or monolingual text property, such as `|?Page.Name|+lang=de`, which rendered an empty column ([#5477](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5477))

## Upgrading

No need to run "update.php" or any other migration scripts, unless you enable the `db-primary-keys`, `elastic-fileingest` or `media` profile with `$smwgConfigProfiles`.

However, if your wiki uses properties of type URL, Annotation URI or Email, run `php maintenance/run.php SemanticMediaWiki:rebuildData` after upgrading. Until then, queries may miss some values stored by an earlier version, such as values containing `%2F`, `'` or `{`, and on SPARQLStore also values containing parentheses or non-ASCII letters.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.3.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
