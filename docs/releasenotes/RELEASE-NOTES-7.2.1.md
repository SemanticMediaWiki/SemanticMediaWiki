# Semantic MediaWiki 7.2.1

Released on TBD.

This is a [patch release](../RELEASE-POLICY.md). Thus, it contains only bug fixes, no new features, and no breaking changes.

Like SMW 7.2.0, this version is compatible with MediaWiki 1.43 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Bug fixes

* Fixed property and category pages sometimes reporting the wrong Semantic MediaWiki protection status, where a page's change-propagation lock and its "Is edit protected" status could be confused for one another ([#4344](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4344))
* Fixed property and category pages sometimes remaining permanently locked for a change propagation update even when no such update was pending, leaving them uneditable; affected pages are editable again, and routine category changes such as setting a parent category no longer trigger the lock ([#4344](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4344))
* Fixed change propagation failing to complete for a property or category connected to a very large number of pages, where selecting the affected pages could exhaust the available memory ([#4344](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4344))
* Fixed Elasticsearch and OpenSearch queries sorted by a Page-datatype property returning results in several independently sorted blocks ([#7079](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7079))
* Fixed Elasticsearch and OpenSearch sort keys losing characters on wikis that set `$smwgEntityCollation` to a `uca-*` value, which made entries that differ only in a number sort as if that number were absent ([#7079](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7079))
* Fixed the `templatefile` result format leaving templates unexpanded in the downloaded file, and the PHP deprecation notice that corrupted it ([#7055](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7055))
* Fixed pages with a very large tooltip failing to render with a `pcre.backtrack_limit exhausted` error; the tooltip's `title` attribute is now capped at 1024 characters, so readers without JavaScript see a truncated native tooltip ([#7076](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7076))
* Fixed a fatal error when a query asked for an inverse property, such as the `?-Has subobject` printout, which stopped the page from being saved or rendered ([#7092](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7092))
* Fixed `update.php` aborting on wikis with a large entity table, where the `smw_hash` conversion ran as one long database statement that a dropped connection discarded in full; it now converts in batches and resumes where an interrupted run stopped ([#7091](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7091))

## Upgrading

No need to run "update.php".

Wikis using Elasticsearch or OpenSearch should run `php maintenance/run.php SemanticMediaWiki:rebuildElasticIndex --only-update` once after upgrading, so that documents indexed before the upgrade are rewritten with the corrected sort keys. It re-indexes in place, without creating new indices or a rollover. Until it has run, the sorting problems listed above persist for every page that is not edited in the meantime.

Note that sorting by a Page-datatype property now orders by the value page's name on Elasticsearch and OpenSearch. Where a value page sets `{{DEFAULTSORT:}}`, results that were last indexed by `rebuildElasticIndex` ordered by that sort key instead, and will change position. The storage back-end continues to order by the sort key.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.2.1`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
