# Semantic MediaWiki 7.4.1

Released on TBD.

This is a [patch release](../RELEASE-POLICY.md). Thus, it contains only bug fixes, no new features, and no breaking changes.

This version is compatible with MediaWiki 1.43.4 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Changes

* Raised the minimum MediaWiki version to 1.43.4. On MediaWiki 1.43.0 to 1.43.3, pages whose `#ask` query shows a "further results" link failed with a `ParameterTypeException` ([#7194](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7194))
* Fixed `updateEntityCollation.php` running very slowly on large wikis ([#7181](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7181))
* Fixed `updateEntityCollation.php` making predefined properties such as `Modification date` sort out of alphabetical order ([#7181](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7181))
* Fixed SMW storing no data for new pages with long sort keys on MySQL and MariaDB in strict SQL mode ([#7202](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7202))
* Fixed `rebuildElasticMissingDocuments.php` running slowly on large wikis ([#7187](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7187))
* Fixed `rebuildData.php` parsing pages twice on large wikis, and made it faster with `--use-job` and `--auto-recovery` ([#7182](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7182))
* Fixed `rebuildFulltextSearchTable.php` emptying the search index for the whole run, running slowly with an InnoDB index table, and changing the order of full-text query results sorted by a property. It now rebuilds in chunks by default ([#7183](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7183))
* Fixed `updateEntityCountMap.php` running slowly on large wikis and ignoring `-s` ([#7184](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7184))
* Fixed `purgeEntityCache.php` running slowly on large wikis ([#7185](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7185))
* Fixed `rebuildElasticIndex.php` running slowly on large wikis ([#7186](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7186))
* Fixed `removeDuplicateEntities.php` deleting every copy of a duplicated property value or redirect when it stopped partway ([#7189](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7189))
* Fixed `updateQueryDependencies.php` stopping with a `TypeError` at a page with a query whose namespace no longer exists ([#7201](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7201))
* Fixed `rebuildPropertyStatistics.php` running slowly on large wikis ([#7188](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7188))
* Fixed update jobs, which `rebuildData.php` also runs, running slowly where SMW's object cache is in the database ([#7191](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7191))
* Fixed the attachments table in the factbox not being sortable ([#7195](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7195))

## Upgrading

If you run MediaWiki 1.43.0 to 1.43.3, update MediaWiki to 1.43.4 or later first, as this version of SMW does not
load on older versions.

No need to run "update.php" or any other migration scripts, unless you ran `updateEntityCollation.php` with an
earlier version. In that case, run "update.php" once so that predefined properties such as `Modification date` sort
by their label again.

If your wiki runs MySQL or MariaDB in strict SQL mode, run `rebuildData.php` once, so that pages with long sort keys
get the data SMW could not store before.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.4.1`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
