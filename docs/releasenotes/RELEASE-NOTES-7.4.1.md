# Semantic MediaWiki 7.4.1

Released on November 10, 2026.

This is a [patch release](../RELEASE-POLICY.md). Thus, it contains only bug fixes, no new features, and no breaking changes.

This version is compatible with MediaWiki 1.43.4 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Security fixes

* Fixed a missing authorization check where an `Is edit protected` annotation set by an editor without the edit-protection right still locked the page, completing the earlier fix for GHSA-9824 ([GHSA-9w3p-9m8x-j2mw](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-9w3p-9m8x-j2mw))
* Fixed a missing authorization check where annotation-driven page protection was authorized against the page's last author instead of the editor making the change, so an editor could remove administrator-set protection through a transcluded template ([GHSA-gh59-2vh3-6cgm](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-gh59-2vh3-6cgm))
* Fixed a cross-site scripting (XSS) vulnerability in `Special:Ask` where the free-format date output format (`?Date#-F[…]`) was emitted without HTML escaping ([GHSA-h252-mj7p-58q7](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-h252-mj7p-58q7))
* Fixed a cross-site scripting (XSS) vulnerability in the `Special:Browse` factbox title where part of a property's preferred label was appended as raw HTML ([GHSA-mc5x-2j7v-x8hp](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-mc5x-2j7v-x8hp))
* Fixed a cross-site scripting (XSS) vulnerability in `Special:Ask` with ElasticStore where the raw query-engine debug output was rendered without HTML escaping, and restricted that debug output to users with the `smw-admin` right ([GHSA-7j96-w9cc-r7rq](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-7j96-w9cc-r7rq))
* Fixed a cross-site scripting (XSS) vulnerability where the DataTables result format rendered author-supplied cell attributes as HTML ([GHSA-gg2q-rc52-22pp](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-gg2q-rc52-22pp))
* Fixed a denial-of-service vulnerability where ElasticStore concept expansion had no guard against mutually recursive concepts, which could exhaust memory ([GHSA-jwx8-mm92-gv95](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-jwx8-mm92-gv95))
* Fixed a denial-of-service vulnerability where concept expansion in the ElasticStore and SPARQLStore query engines was not bounded, so an acyclic concept fan-out could exhaust memory ([GHSA-24v2-mf3g-84ch](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-24v2-mf3g-84ch))
* Fixed a cross-site scripting (XSS) vulnerability in `Special:FacetedSearch` where profile keys were interpolated into the profile option list without HTML escaping ([GHSA-cw86-j46r-2x7x](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-cw86-j46r-2x7x))

## Changes

* Raised the minimum MediaWiki version to 1.43.4. On MediaWiki 1.43.0 to 1.43.3, pages whose `#ask` query shows a "further results" link failed with a `ParameterTypeException` ([#7194](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7194))
* Added warnings when MediaWiki parses pages with Parsoid ([#7207](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7207))
* Fixed `updateEntityCollation.php` running very slowly on large wikis ([#7181](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7181))
* Fixed `updateEntityCollation.php` making predefined properties such as `Modification date` sort out of alphabetical order ([#7181](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7181))
* Fixed SMW storing no data for new pages with long sort keys on MySQL and MariaDB in strict SQL mode ([#7202](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7202))
* Fixed `rebuildElasticMissingDocuments.php` running slowly on large wikis ([#7187](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7187))
* Fixed `rebuildData.php` parsing pages twice on large wikis, and made it faster with `--use-job` and `--auto-recovery` ([#7182](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7182))
* Fixed `rebuildFulltextSearchTable.php` emptying the search index for the whole run, running slowly with an InnoDB index table, and changing the order of full-text query results sorted by a property. It now rebuilds in chunks by default ([#7183](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7183))
* Fixed property pages opened with the `until` URL parameter listing the first pages of the list, in reverse order, instead of the pages just before the given page. Their previous and next links now work as on other property pages ([#7203](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7203))
* Fixed `updateEntityCountMap.php` running slowly on large wikis and ignoring `-s` ([#7184](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7184))
* Fixed `purgeEntityCache.php` running slowly on large wikis ([#7185](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7185))
* Fixed `rebuildElasticIndex.php` running slowly on large wikis ([#7186](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7186))
* Fixed `removeDuplicateEntities.php` deleting every copy of a duplicated property value or redirect when it stopped partway ([#7189](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7189))
* Fixed `updateQueryDependencies.php` stopping with a `TypeError` at a page with a query whose namespace no longer exists ([#7201](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7201))
* Fixed `rebuildPropertyStatistics.php` running slowly on large wikis ([#7188](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7188))
* Fixed update jobs, which `rebuildData.php` also runs, running slowly where SMW's object cache is in the database ([#7191](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7191))
* Fixed property pages, other lists of the pages that use a property, `Special:FacetedSearch` and `updateQueryDependencies.php` failing on MariaDB with "isn't in GROUP BY" when the SQL mode includes `ONLY_FULL_GROUP_BY`, as MediaWiki's `DevelopmentSettings.php` sets it ([#7196](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7196))
* Fixed SMW on PostgreSQL listing a page once per value on property pages and in other lists of the pages that use a property ([#7204](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7204))
* Fixed the attachments table in the factbox not being sortable ([#7195](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7195))
* Fixed `format=feed` exports failing on MediaWiki 1.47 ([#7206](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7206))
* Fixed Elasticsearch replication and `rebuildElasticIndex.php` with `indexer.raw.text` enabled failing on MediaWiki 1.47, and on earlier versions for pages with some content models that are not text ([#7206](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7206))
* Fixed pages keeping outdated property values, and queries missing them, when several job runners or other processes updated pages at the same time. A page update that fails because of a database error is now retried by an update job ([#7208](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/7208))

## Upgrading

If you run MediaWiki 1.43.0 to 1.43.3, update MediaWiki to 1.43.4 or later first, as this version of SMW does not
load on older versions.

Run [update.php](https://www.mediawiki.org/wiki/Manual:Update.php) once.

If your wiki runs MySQL or MariaDB in strict SQL mode, run `rebuildData.php` once, so that pages with long sort keys
get the data SMW could not store before.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.4.1`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
