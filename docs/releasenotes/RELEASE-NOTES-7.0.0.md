# Semantic MediaWiki 7.0.0

Released on June 4, 2026.

This release makes Semantic MediaWiki easier to install and run, brings significant query and indexing performance improvements, and adds MediaWiki 1.45 and 1.46 support. Installation now follows standard MediaWiki conventions, configuration accepts plain strings instead of `SMW_*` constants, and install-state metadata moves into the database. If you maintain an extension or integration that depends on SMW, see [For developers and extension authors](#for-developers-and-extension-authors) and the [migration guide](../migration/7.0.md) for the removed and changed APIs.

## Compatibility

* Added support for MediaWiki 1.45 and 1.46 (new compared to SMW 6.0.x, which supported up to 1.44)
* Added support for PHP 8.5 (new compared to SMW 6.0.x, which supported up to 8.4)
* Compatible with PHP 8.1 up to 8.5 and MediaWiki 1.43 up to 1.46

For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Highlights

Adds MediaWiki 1.45 and 1.46 support (see [Compatibility](#compatibility)).

**Easier to install and run, aligned with MediaWiki conventions.** SMW now uses the standard MediaWiki mechanisms instead of its own bespoke ones.

* [`enableSemantics()` is no longer required](#deprecated): plain `wfLoadExtension( 'SemanticMediaWiki' )` is enough.
* [String-based configuration](#configuration-changes) means no more `SMW_*` constants.
* [Install-state moved from `.smw.json` to the database](#action-required-when-upgrading), so no shared filesystem is needed for multi-server setups.
* [`smw-admin` is granted to `sysop` by default](#new-features-and-enhancements), so admins reach `Special:SemanticMediaWiki` out of the box.
* [Namespaces relocate via standard MediaWiki `define()` constants](#action-required-when-upgrading) instead of `$smwgNamespaceIndex` (MediaWiki core's documented mechanism since 1.30).

**Significant performance improvements.**

* [Query sort speedups](#faster-property-queries) (orders of magnitude on large wikis), `order=none`, and cursor-based pagination.
* [Lazy dependency refresh](#lazy-dependency-refresh) and reduced job queue load on edits and deletes of widely-referenced pages.
* [Parser cache defragmentation](#parser-cache-defragmentation): pages with language-neutral `#ask` output or in-text annotations are now cached once and shared across all interface languages instead of stored separately per language. The cache holds far fewer redundant copies of the same page, raising the hit rate and reducing the eviction of still-useful entries.

**Built on MediaWiki core.**

* [Bundled third-party libraries dropped](#removed) in favor of MediaWiki core services (`Onoi\Tesa`, `Onoi\HttpRequest`, `onoi/callback-container`, `onoi/cache`, `onoi/blob-store`), and a large batch of long-deprecated APIs removed. Extension authors: see the [migration guide](../migration/7.0.md).

## For users and administrators

### Action required when upgrading

* **Fulltext search reindex required.** The vendored `Onoi\Tesa` text sanitizer has been replaced with PHP `intl` built-ins. If you have `smwgEnabledFulltextSearch` enabled, run `rebuildFulltextSearchTable.php` after upgrading. Transliteration now uses ICU instead of a static mapping table, which produces minor differences for some characters (e.g., German ü→u instead of ü→ue).
* **`$smwgNamespaceIndex` removed; namespace IDs now relocate via PHP constants.** SMW's six custom namespaces (`SMW_NS_PROPERTY`, `SMW_NS_PROPERTY_TALK`, `SMW_NS_CONCEPT`, `SMW_NS_CONCEPT_TALK`, `SMW_NS_SCHEMA`, `SMW_NS_SCHEMA_TALK`) are now declared in `extension.json`'s `namespaces` block, and the `$smwgNamespaceIndex` setting is gone. To use non-default namespace IDs, define the constants directly in `LocalSettings.php` BEFORE `wfLoadExtension( 'SemanticMediaWiki' )` (this is MediaWiki core's documented relocation mechanism since MW 1.30):

  ```php
  define( 'SMW_NS_PROPERTY',      202 );
  define( 'SMW_NS_PROPERTY_TALK', 203 );
  define( 'SMW_NS_CONCEPT',       208 );
  define( 'SMW_NS_CONCEPT_TALK',  209 );
  define( 'SMW_NS_SCHEMA',        212 );
  define( 'SMW_NS_SCHEMA_TALK',   213 );

  wfLoadExtension( 'SemanticMediaWiki' );
  ```

  Wikis that still set `$smwgNamespaceIndex` in `LocalSettings.php` after upgrading will fail to boot with a `RemovedNamespaceIndexException` containing the matching `define()` block, calculated from the previous offset, ready to copy into `LocalSettings.php`. `\SMW\Exception\NamespaceIndexChangeException` is removed (unreachable).

* **Install-state metadata moved from `.smw.json` to the database.** Upgrade key, maintenance mode, incomplete-task flags, version tracking, database requirements, last optimization run, and entity collation now live in a new `smw_meta` table. The upgrade run merges your existing `.smw.json` state into the table (preserving incomplete-task flags and other survivor keys) and then renames the file to `.smw.json.migrated`. Re-running the upgrade is safe. Multi-server deployments no longer need shared filesystem storage for install state. The legacy setting `$smwgConfigFileDir` is kept only so the upgrade can find non-default file locations (see Deprecated). Resolves [#3506](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3506).

  When the database is unreachable, the install-state gate can no longer fall back to the file. A database outage now surfaces as MediaWiki's standard database error page instead of SMW's "service unavailable" page; monitoring and runbooks keyed on the SMW-specific page should be updated.

* **`loadDefaultConfigFrom()` removed.** The method on the return value of `enableSemantics()` has been removed and will fatal if called. Replace with a direct `require`:

  ```php
  // Before (broken — will fatal)
  enableSemantics( 'example.org' )->loadDefaultConfigFrom( 'media.php' );

  // After
  wfLoadExtension( 'SemanticMediaWiki' );
  require "$IP/extensions/SemanticMediaWiki/data/config/media.php";
  ```

  Note: `enableSemantics()` itself still exists but is deprecated as a no-op (see Deprecated).

* **SMW's `<section>` parser tag renamed to `<smwsection>`.** The tag that marks up property-page specification content is now `<smwsection>…</smwsection>`, so it no longer collides with the `<section>` tag registered by extensions such as [LabeledSectionTransclusion](https://www.mediawiki.org/wiki/Extension:Labeled_Section_Transclusion). The rendered output is unchanged (still an HTML `<section class="smw-property-specification">` element), so styling and property-page tabs behave exactly as before; only the wikitext you type changes. Saved pages are not migrated automatically: update any page (typically in the `Property:` namespace) that uses `<section>` for SMW by replacing the opening and closing tags with `<smwsection>` and `</smwsection>`. The `$smwgSupportSectionTag` setting still controls whether SMW registers its tag, and most wikis no longer need to disable it. ([#5687](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5687))

### New features and enhancements

* The `smw-admin` right is now granted to the `sysop` group by default, so wiki administrators can reach `Special:SemanticMediaWiki` out of the box without first joining the `smwadministrator` group. The `smwadministrator` group is retained for installs that want SMW administration to be a separate role; revoke the new default with `$wgGroupPermissions['sysop']['smw-admin'] = false;` in `LocalSettings.php`.
* Changed `smw_hash` storage from hex-encoded to raw binary, reducing the hash index size and improving query performance on large wikis. Column type changes from `VARBINARY(40)` to `BINARY(20)` on MySQL/MariaDB and SQLite, and from `TEXT` to `BYTEA` on PostgreSQL. Existing hashes are converted automatically during `update.php`. ([#6587](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6587))
* Removed a redundant single-column index from the wikipage value tables (`smw_di_wikipage` and the fixed page-property tables). The index duplicated the leading column of a composite index that already serves the same lookups, so dropping it lowers index size and per-edit write overhead with no change to any query plan. The index is removed automatically during `update.php`. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
* Improved pagination performance on Special:Properties and Special:UnusedProperties by switching from OFFSET-based to cursor-based pagination. Browsing deep pages is now significantly faster on wikis with many properties. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
  * Navigation links now use `after=` and `before=` URL parameters instead of `offset=`. Existing `offset=` bookmarks continue to work.
  * The numbered result list has been replaced with a bullet list, and the "starting with #N" indicator has been removed, as cursor-based pagination does not track absolute position.
* The `smwbrowse` API (used by `browse=property`, `browse=category`, `browse=concept`) now supports opt-in cursor pagination. Clients that send `cursor` in the request payload receive a `query-continue-cursor` field in the response and walk forward via keyset seeks instead of OFFSET. Old clients that follow `query-continue-offset` continue to work unchanged. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
<a id="faster-property-queries"></a>
* `#ask` queries that sort by a property value are now significantly faster on MariaDB and MySQL. The query engine restructures the SQL so the database can choose a more efficient plan; on large wikis the improvement can be orders of magnitude depending on query shape. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
  * Set `$smwgQUseLegacyQuery = true` in `LocalSettings.php` to fall back to the previous query shape if you encounter a regression after upgrading.
  * A redundant `DISTINCT` keyword was also dropped from the disjunction-query temp-table insert. Same result, less work for the database; no setting required.
* On wikis with many distinct entities per page, SMW's internal caches could fill up during a single render and force repeated database lookups for the same pages. Cache sizes are now adjustable via the new `$smwgEntityCacheSizes` setting. Per-pool hit and miss counts are also emitted to MediaWiki's `StatsFactory` service, so wikis already configured to collect MediaWiki metrics (`$wgStatsTarget` and `$wgStatsFormat`) can see cache effectiveness in their existing dashboards and size caches based on real traffic instead of guessing.
<a id="parser-cache-defragmentation"></a>
* `#ask` queries no longer fragment the parser cache by the user's date format preference. SMW formats dates by language, not by that preference, so the fragmentation produced no benefit. `dateformat` has been removed from the `$smwgSetParserCacheKeys` default, which now contains only `userlang`.
* `#ask` queries whose result format produces language-neutral output (`json`, `rdf`, `count`, `debug`) no longer fragment the parser cache by `userlang`, unless the query produces errors (error messages are localised). Result printers can declare their own behaviour by overriding `ResultPrinter::dependsOnUserLanguage()`.
* On multilingual wikis, the common `#ask` presentation formats (`table`, `broadtable`, `list`, `ul`, `ol`, `plainlist`, `template`, `embedded` and `category`) no longer store a separate parser cache entry for each interface language. A successful result is cached once and reused across all languages, improving the parser cache hit rate. Queries that produce errors still vary by language, because error messages are localised.
* Pages that use in-text property annotations (`[[Property::value]]`) are likewise no longer cached separately for each interface language when the annotation output is language-neutral, which is the common case. Pages whose annotations render language-specific output, such as a localised value or an error, still vary by language.
* `#ask` queries now accept `order=none` to skip result sorting entirely. Every `#ask` query previously sorted by page, which on large result sets forces the database to sort the whole intermediate set before applying the limit. With `order=none` the query engine emits no `ORDER BY`, so the limit can short-circuit; on large queries this can be orders of magnitude faster. Results are returned in an unspecified order, and `order=none` cannot be combined with cursor pagination. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
* Parsing pages with many subobjects is now significantly faster. SMW resolved the page content language once for every property value, so a page with hundreds of subobjects triggered thousands of repeated lookups of the same page's language. The result is now memoized per page within a request. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
* `#ask` queries whose condition matches against a large list of pages or categories (for example a big `[[Property::A||B||...]]` value list) now resolve those pages in a single batched lookup while the query is compiled, instead of one database query per value. On wikis that allow very large conditions this removes thousands of per-value round-trips from a single query. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
<a id="lazy-dependency-refresh"></a>
* Property and Category page edits no longer force a re-parse of every dependent when only display-only annotations change (`_SUBC`, `_SUBP`, `_PDESC`, `_PPLB`). On wikis with thousands of dependents the job runner cost drops from hours to seconds. ([#6880](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6880))
* On wikis with `smwgEnabledQueryDependencyLinksStore` enabled, deleting a page no longer queues a forced re-parse of every other page whose `#ask` queries referenced it. Dependents refresh lazily on next view via the existing `DependencyValidator` invalidation path. For deletions of widely-referenced subjects the job runner cost drops to zero at delete time; the parse cost is paid only on view of dependents that are actually visited. ([#6881](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6881))
* On wikis with `smwgEnabledQueryDependencyLinksStore` enabled, viewing a page whose `#ask` query dependencies are stale now triggers exactly one re-parse instead of two. The previous behavior re-parsed once uncached and then re-parsed a second time to populate the parser cache; the new flow uses MediaWiki's normal `RejectParserCacheValue` cache-rejection path so a single fresh parse is saved normally. No configuration change required. ([#6882](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6882))
* Creating a redirect to a page, or moving a page onto an existing title, no longer re-parses the target page synchronously on the web server. Previously the forced re-parse ran in a post-send deferred update in the same web request, so creating many redirects to one expensive target in quick succession (for example via the API) could saturate the server with repeated parses. The re-parse is now pushed to the job queue, where MediaWiki's job deduplication collapses repeated updates to the same target into a single job. This applies when `smwgEnableUpdateJobs` is enabled (the default); command-line invocations and wikis with the update-job queue disabled keep the previous synchronous behavior, so the redirect cleanup from [#895](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/895) is preserved in all configurations. ([#5619](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5619))
* `$wgSearchType` can now be set to `SMW\MediaWiki\Search\ExtendedSearchEngine` to enable the extended search, alongside the deprecated `SMWSearch` alias. ([#6944](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6944))
* Date values rendered in HTML (result tables, the factbox, `Special:Browse`, `Special:SearchByProperty`) are now wrapped in a semantic `<time datetime>` element, exposing a machine-readable date to assistive technology and other consumers while the displayed text is unchanged. Exports (CSV, JSON, RDF) stay plain. ([#6830](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6830))
* `rebuildData.php` accepts a new `--use-job` option that queues its update jobs instead of running them inline, so a full rebuild can be processed in parallel with `runJobs.php --type smw.update --procs N`. The per-entity parse cost is unchanged, so set the worker count to what your database can absorb. ([#6952](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6952))
* Outdated-entity disposal now removes references in batched IN-list deletes, making `disposeOutdatedEntities.php` and the rebuild disposal prologue substantially faster on large wikis. ([#6968](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6968))
* `disposeOutdatedEntities.php` accepts new `--of`/`--shard` options to run disposal across several parallel processes over disjoint `smw_id` shards (query-link cleanup runs on shard 0). ([#6968](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6968))
* `rebuildData.php` accepts a new `--skip-dispose` option to skip the disposal prologue, enabling parallel ranged rebuilds after a single (optionally sharded) disposal run. ([#6968](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6968))

### Bug fixes

* Fixed `Special:FacetedSearch` not showing the result row when a query returns a single result ([#6260](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6260))
* Fixed a fatal error on `Special:Search` when `$wgSearchType` is set to `SMWSearch` ([#6915](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6915))
* Fixed SMW tooltips and popups not appearing since 5.0.0 (`Special:Ask` info icons, property page value tooltips, and the job queue watchlist popup), and restored the job queue watchlist indicator's visibility in modern skins ([#6075](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6075))
* `rebuildData.php --ignore-exceptions` now also skips and logs PHP errors (such as a `TypeError` raised while parsing a single page), so one un-parseable page no longer aborts the whole rebuild ([#6218](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6218))
* Fixed a stray trailing space in `'edit '` that caused the edit-protection check in `ParserAfterTidy::process()` to silently match no pages since the migration off the deprecated `Title::isProtected()` API. With the typo removed, edit-protected pages once again trigger continued post-parse processing, matching the behaviour before that migration.
* Fixed incorrect timezone offset for negative half-hour timezones (e.g., Newfoundland `-3:30`): the 30-minute component was always added positively, producing `-2.5` instead of `-3.5` ([#6478](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6478))
* Fixed CSV export producing malformed output when values contain the delimiter character ([#6343](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6343))
* Fixed `#ask` sum format failing when encountering non-numeric values instead of treating them as zero ([#6253](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6253))
* Fixed sortkey being silently dropped in certain query result contexts ([#6250](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6250))
* Fixed template-rendered values with HTML tags breaking when used with named parameters ([#6235](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6235))
* Fixed `limit`, `offset`, and `default` parameters being ignored in `@deferred` queries ([#6233](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6233))
* Fixed malformed Page-type values producing unsanitized error messages ([#6234](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6234))
* Fixed long property values being incorrectly truncated in the SQL store ([#6225](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6225))
* Fixed content namespaces configuration failing when array keys are numeric ([#6293](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6293))
* Fixed JavaScript configuration variables missing after deferred query execution ([#6266](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6266))
* Fixed unwanted bullet-point styling in FacetedSearch result list ([#6287](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6287))
* Fixed empty `<section>` tags producing broken output ([#6521](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6521))
* Fixed `CannotCreateActorException` when importing pages on wikis with temporary accounts enabled ([#6331](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6331))
* Fixed deprecation warnings on PostgreSQL ([#6202](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6202))
* Fixed dynamic property deprecation warnings on PHP 8.2+ ([#6362](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6362))
* Fixed PHP notices in `db-primary-keys.php` maintenance script ([#6466](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6466))
* Fixed float-to-int precision loss in maintenance script progress output ([#6229](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6229))
* Fixed null argument error in entity lookup task handler ([#6228](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6228))
* Improved wording of the post-edit reload notice ([#6301](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6301))
* Fixed maintenance log entries showing "performed unknown action" instead of a proper message, and improved log comment formatting from raw JSON to human-readable text ([#6146](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6146), [#6554](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6554))
* Fixed `update.php` failing with "Data too long for column 'smw_hash'" on wikis with more than 200,000 entities. The pre-upgrade hex-to-binary conversion now always runs as a single server-side `UPDATE`, regardless of row count. Setting `$smwgIgnoreUpgradeKeyCheck = true` now also lets maintenance scripts run when the schema is in an intermediate state, providing a documented escape hatch for stalled upgrades. ([#6715](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6715))
* Removing `userlang` or `dateformat` from `$smwgSetParserCacheKeys` now correctly stops SMW from adding that key to the parser cache key. Previously a removed key was still added through a different mechanism.
* Fixed dependency tracking for pages embedding multiple `#ask` queries that share conditions but request different printouts. Changes to a printout-only property now correctly invalidate the embedding page's cached query results.
* Fixed slow property-subject lookups on large wikis, where a query-planner index hint forced a near-full table scan for properties that are common overall yet sparse relative to the wiki. The hint threshold now scales with the size of the wiki, so it is skipped where it would hurt and kept where it helps. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
* Fixed date columns in the `table` and `broadtable` result formats not sorting chronologically under locales that use `.` as a digit-group separator, such as German ([#6830](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6830))
* Local time in `#ask` / `#show` output (`#LOCL#TO`) is now converted to each viewer's time zone in the browser. Previously it was rendered server-side and shared through the parser cache, so every viewer saw the time zone of whoever first populated the cache. Without JavaScript the wiki's local time is shown. The non-functional per-user parser-cache key (`localTime`) registered by the previous mechanism has been removed. ([#6820](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6820))
* Fixed PHP 8.2 dynamic property deprecation warnings when reading data items from caches written by a pre-7.0 version ([#6965](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6965))

### Configuration changes

* **String-based configuration values.** Settings that took `SMW_*` constants now accept plain strings, and bitmask settings take arrays of strings:

  ```php
  // Before
  $smwgShowFactbox     = SMW_FACTBOX_NONEMPTY;
  $smwgFactboxFeatures = SMW_FACTBOX_CACHE | SMW_FACTBOX_PURGE_REFRESH;

  // After
  $smwgShowFactbox     = 'nonempty';
  $smwgFactboxFeatures = [ 'cache', 'purge-refresh' ];
  ```

  The constant form still works in 7.x and emits a deprecation notice; it will be removed in 8.0. The new form fixes the "undefined constant" errors that occurred when `LocalSettings.php` referenced these settings before SMW had loaded. Unknown strings are ignored with a structured-log warning. See the [migration guide](../migration/7.0.md#string-based-configuration) for the full mapping of every affected setting and its accepted values.

  `$smwgQueryProfiler`'s `extension.json` default changes from `true` to `[]`; behaviour is unchanged because both forms produce zero flag bits in `Options::isFlagSet`. Note that `[]` enables basic profiling (no detail fields), it does NOT disable; use `false` to disable profiling entirely. The legacy `true` form is also accepted with a deprecation notice and will be removed in 8.0.

* **Legacy setting auto-translation removed.** The runtime shim that silently rewrote settings deprecated in SMW 3.1 and 3.2 to their replacements is gone. Update any of the old names in `LocalSettings.php` to their replacement; otherwise the legacy names are silently ignored. Special:Admin no longer surfaces them as deprecation notices. See the [migration guide](../migration/7.0.md#removed-legacy-settings) for the full old-name to replacement mapping.
* **`$smwgSchemaTypes` removed.** Register custom schema types through the `SMW::Schema::RegisterSchemaTypes` hook (available since 3.2). Any entries left in `$smwgSchemaTypes` after upgrade are silently ignored — port them to a hook handler first. Hook signature: `src/Schema/README.md`.
* **SPARQL HTTP configuration removed.** The `$smwgSparqlRepositoryConnectorForcedHttpVersion` setting no longer exists. SPARQL store connectors and `RemoteRequest` now use MediaWiki core's `HttpRequestFactory` for HTTP version negotiation. The `mediawiki/http-request` (`Onoi\HttpRequest`) dependency has been dropped.
* **Transaction profiler warnings no longer silenced.** SMW previously silenced MediaWiki's `TransactionProfiler` for every database write. As of 7.0.0, warnings (`Suboptimal transaction […]` and similar) reach the standard `rdbms` log channel where site admins can observe them.

  If you see new log spam after upgrading, raise the budget via `$wgTrxProfilerLimits` (e.g. `$wgTrxProfilerLimits['POST']['maxAffected'] = 5000;`) or point `$wgDebugLogGroups['rdbms']` at a discard target.

## For developers and extension authors

### Removed

**Dependencies and autoloading:**

* Removed the `mediawiki/parser-hooks` dependency.
* Removed `psr/log` from `composer.json`. Extensions that relied on SMW pulling in `psr/log` transitively must declare it in their own `composer.json`.
* Removed the `mediawiki/callback-container` (`onoi/callback-container`) Composer dependency. The internal DI layer now uses MediaWiki's `Wikimedia\Services\ServiceContainer` directly ([#6428](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6428)).
* Removed the `onoi/blob-store` Composer dependency. SMW's durable query-result cache now uses the in-tree `SMW\Query\Cache\QueryResultStore` and `QueryResultContainer` over MediaWiki's `Wikimedia\ObjectCache\BagOStuff`, with the cache key format and payload encoding preserved so existing entries round-trip unchanged. This was internal infrastructure and never part of the public API.
* Removed the `onoi/cache` Composer dependency. SMW's caches now use MediaWiki-native primitives directly: `Wikimedia\ObjectCache\BagOStuff` over the configured `$smwgMainCacheType`, and the in-tree `SMW\Cache\InMemoryLruCache` (over `MapCacheLRU`) for the in-process pool caches. The `SMW.Cache` composite service, `ServicesFactory::getCache()`, and `CacheFactory`'s Onoi cache-minting methods are gone. This was internal infrastructure and never part of the public API.
* Removed the `@private` internal class `SMW\Services\SharedServicesContainer` and the internal wiring files `src/Services/mediawiki.php`, `src/Services/events.php`, and `src/Services/cache.php`. These were never part of the public API ([#6428](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6428)).
* Removed the root `DefaultSettings.php` shim (deprecated since 4.0.0). Use `$GLOBALS['smwgFoo']` or `SMW\Settings::getInstance()->get('smwgFoo')` instead.
* Removed `Defines.php`.
* **`includes/` directory removed.** All classes have moved to `src/` under new namespaces (`DataItems/`, `DataValues/`, `Export/`, `Formatters/`, `Query/`, `QueryPages/`, `MediaWiki/Specials/`). Class aliases are provided for the transition (see Deprecated below), but code that loaded files by path (e.g., `require .../includes/dataitems/...`) will break.

**Configuration and runtime APIs:**

* **`SMW\SemanticMediaWiki::getDefaultSettings()` and `SMW\SemanticMediaWiki::setupGlobals()` removed.** SMW's defaults now come from `extension.json`'s `config` block (and a small registration callback for constants/paths) instead of the bespoke `src/DefaultSettings.php` array + `setupGlobals()` seeding. External code that called `getDefaultSettings()` should read globals directly via `$GLOBALS['smwgFoo']` or via `SMW\Settings::getInstance()->get('smwgFoo')`.
* **`src/DefaultSettings.php` removed.** Per-setting documentation that previously lived as inline comments in this file now lives at `docs/config.md` (one section per setting) and in the manifest's `description` field. Authoring `LocalSettings.php` is unchanged — `$smwgFoo = …;` continues to work for every setting that ever did.

**Removed APIs:**

* **Cache factory naming cleanup.** `ServicesFactory::newBlobStore()` and `CacheFactory::newBlobStore()` are renamed to `newQueryResultStore()`, and the `'BlobStore'` service route is now `'QueryResultStore'`, reflecting that they construct a `SMW\Query\Cache\QueryResultStore` (the former `onoi/blob-store` is gone). `CacheFactory::getPurgeCacheKey()` is removed; build the key with `smwfCacheKey( SMW\MediaWiki\Hooks\ArticlePurge::CACHE_NAMESPACE, $articleId )`. `SMW\InMemoryPoolCache`'s constructor no longer takes a `CacheFactory` argument. These were internal infrastructure.
* **`browsebyproperty` and `browsebysubject` API modules removed.** Both were deprecated since 3.0. Use the `smwbrowse` API module (`action=smwbrowse`) instead.
* **`SMW\MediaWiki\Api\PropertyListByApiRequest` removed.** This was an internal helper for the now-removed `browsebyproperty` module; no other code consumed it. External consumers should query the `smwbrowse` API module directly.
* **`SMW\MediaWiki\Specials\SpecialPage` base class removed**, along with the legacy `SMW\SpecialPage` alias. All in-tree SMW special pages now extend MediaWiki core's `MediaWiki\SpecialPage\SpecialPage` directly and receive `Store` / `Settings` through their constructor (registered via `SpecialPages` ObjectFactory specs in `extension.json`). Third-party special pages that extended the SMW base class must extend MediaWiki core's `SpecialPage` and inject the services they need.
* **`SMW\MediaWiki\Api\Query` gained a required `QuerySourceFactory` constructor argument.** The base class for SMW's query API modules now takes `SMW\Query\QuerySourceFactory` as its third constructor parameter. SMW's own `ask` / `askargs` modules are registered with the `SMW.QuerySourceFactory` service so MediaWiki's ObjectFactory injects it; third-party API modules subclassing `Api\Query` must add the same `services` entry to their `extension.json` registration, otherwise instantiation fails with an `ArgumentCountError`. See the [migration guide](../migration/7.0.md#query-api-module-subclasses-now-require-a-querysourcefactory).
* **Legacy DML methods on `SMW\MediaWiki\Connection\Database` removed.** Removed methods: `select()`, `selectRow()`, `selectField()`, `estimateRowCount()`, `insert()`, `update()`, `delete()`, `upsert()`, `replace()`, and the `makeSelectOptions()` passthrough. `Database::query()` and `Database::readQuery()` also tightened from `Query|string` to `string` only. SMW's database wrapper is internal infrastructure; external code that called these methods directly on `$store->getConnection( 'mw.db' )` should migrate to MediaWiki core's database services. See [Manual:Database access](https://www.mediawiki.org/wiki/Manual:Database_access).
* Removed `getTextFromContent()`, `replacePrefixes()`, and `textAlreadyUpdatedForIndex()` from `ExtendedSearchEngine`, matching their removal from MediaWiki core's `SearchEngine`.
* Removed the `SMW\Site::searchType()` method. It was a thin `$GLOBALS['wgSearchType']` accessor whose only caller, the `SpecialSearchProfiles` hook, now reads the search type from MediaWiki core's `SearchEngineConfig::getSearchType()`, the same source used to construct the search engine.
* Removed unused internal classes: `HtmlVTabs`, `SchemaParameterTypeMismatchException`, `CleanUpTables`, and `FlatSemanticDataSerializer`.
* Removed the internal `SMW\Utils\TemplateEngine` class and its bundled `.ms` templates under `data/template/`. All consumers now render through MediaWiki core's `MediaWiki\Html\TemplateParser` with Mustache templates.
* Removed several internal wrappers around MediaWiki core services. The `SMW\MediaWiki\FileRepoFinder` class (its `findFile()` was a pure `RepoGroup::findFile()` passthrough; `findFromArchive()` had no production callers) and its `ServicesFactory::getFileRepoFinder()` accessor are gone; the single production caller (`SMW\Elastic\Indexer\Attachment\FileHandler`) now takes a `RepoGroup` directly. `SMW\MediaWiki\PageInfoProvider::isProtected()` is removed; callers now use `MediaWikiServices::getInstance()->getRestrictionStore()->isProtected()` directly. The unused `SMW\MediaWiki\RedirectTargetFinder::hasContentHandler()` method is also removed.
* Removed the `fetchContentFromURL()` and `setReadCallback()` methods from `SMW\Elastic\Indexer\Attachment\FileHandler`. The Elasticsearch attachment indexer reads file content directly from the file backend via `fetchContentFromFile()` since [#6138](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6138), so the HTTP-fetch path these provided had zero production callers (only the now-removed unit test exercised them). The class's `LoggerAwareTrait` and the matching `setLogger()` injection in `ElasticFactory` are dropped with them, since nothing reads the logger anymore.
* Removed `SMW\MediaWiki\Permission\PermissionExaminer::setUser()`. The class's `User` is always injected at construction time via `ServicesFactory::newPermissionExaminer()`; the lone caller in `GetPreferences::process()` was redundant. Tests should pass the user as the constructor's second argument.
* Removed the internal classes `SMW\MediaWiki\Preference\PreferenceExaminer` and `SMW\MediaWiki\Preference\PreferenceAware`, along with `ServicesFactory::newPreferenceExaminer()`. `PreferenceExaminer` was a thin wrapper over MediaWiki core's `UserOptionsLookup`; callers now use `UserOptionsLookup::getOption()` directly. `PreferenceAware` was an interface with no implementors. Neither was part of the public API.
* Removed the `STAGE_PRESEND` / `STAGE_POSTSEND` constants and the `asPresend()` / `getStage()` methods from `SMW\MediaWiki\Deferred\CallableUpdate`. The stage routing they implemented had zero production callers (only the now-removed unit test exercised them), and the registration path against `DeferredUpdates::addUpdate()` no longer threads a stage argument. All updates default to `POSTSEND` as before.
* Removed the `setFingerprint()` / `getFingerprint()` methods and the static `$queueList` from `SMW\MediaWiki\Deferred\CallableUpdate` (and its `TransactionalCallableUpdate` subclass). The same setter is also removed from `SMW\MediaWiki\PageUpdater`, and the now-unused private `$fingerprint` property on `SMW\Utils\Stats` is gone with it. Callers that want to dedup duplicate `pushUpdate()` invocations should track that themselves.
* Removed the internal `SMW\MediaWiki\ManualEntryLogger` class along with `ServicesFactory::getManualEntryLogger()` and the `SMW.ManualEntryLogger` service. `ManualEntryLogger` wrapped `ManualLogEntry` with a registry of permitted event types; the registry was always satisfied at each callsite, so the gate added no value. Internal callers (`MaintenanceLogger`, `EntityLookupTaskHandler`) now construct `ManualLogEntry` directly. `MaintenanceLogger`'s constructor loses its second `ManualEntryLogger` parameter.
* Removed the internal `SMW\MediaWiki\TitleFactory` class along with `ServicesFactory::newTitleFactory()`, `ServicesFactory::getTitleFactory()`, and the `SMW.TitleFactory` service. `TitleFactory` was a thin wrapper around MediaWiki core's `\MediaWiki\Title\TitleFactory` (available since MW 1.35) plus a `createPage()` / `createFilePage()` delegation to `WikiPage`. Callers now obtain MediaWiki core's `TitleFactory` from `MediaWikiServices::getInstance()->getTitleFactory()` and use `MediaWiki\Page\WikiPageFactory` for `WikiPage` construction; the SMW wrapper's `newFromIDs()` batch-lookup was inlined as a private helper in `SMW\SQLStore\Rebuilder\Rebuilder` (its only caller). `TextContentCreator`'s constructor gains a third `WikiPageFactory` parameter.
* Removed the internal `SMW\MediaWiki\Connection\LoadBalancerConnectionProvider` and `SMW\Connection\CallbackConnectionProvider` classes. Both implemented the `SMW\Connection\ConnectionProvider` interface as thin lazy-init wrappers (one over `ILoadBalancer::getConnection()`, the other over an arbitrary callable). `MwCollaboratorFactory::newLoadBalancerConnectionProvider()` now returns an anonymous `ConnectionProvider` implementation directly; the return type changes from `LoadBalancerConnectionProvider` to `SMW\Connection\ConnectionProvider`. `ConnectionManager::registerCallbackConnection()` is unchanged in behaviour. Code that type-hinted parameters or property declarations against the concrete `LoadBalancerConnectionProvider` class must switch to the `SMW\Connection\ConnectionProvider` interface; references to `CallbackConnectionProvider` were undocumented and had no callers outside the wrapper itself.
* Removed nine pure-delegation accessor methods on `SMW\Services\ServicesFactory`: `getLoadBalancer()`, `getDBLoadBalancer()`, `getDBLoadBalancerFactory()`, `getMainConfig()`, `getSearchEngineConfig()`, `getMagicWordFactory()`, `getContentLanguage()`, `getParserCache()`, and `getUserOptionsLookup()`. Each was a thin pass-through to the corresponding `MediaWikiServices::getInstance()->getX()` accessor with a testOverrides hook that no test consumed. SMW now resolves these MediaWiki core services through `MediaWikiServices` directly. The matching `'DBLoadBalancer'`, `'DBLoadBalancerFactory'`, `'MainConfig'`, `'SearchEngineConfig'`, `'MagicWordFactory'`, `'ContentLanguage'`, `'ParserCache'`, and `'UserOptionsLookup'` entries on the `ServicesFactory::singleton()` / `ServicesFactory::create()` dispatch table are removed too, as is the unused `SMW.SearchEngineConfig` service wiring. `ServicesFactory::getJobQueueGroup()` and its `'JobQueueGroup'` dispatch entry are retained because the `SMW.JobQueue` closure in `ServiceWiring.php` resolves the inner `JobQueueGroup` through it so `$testEnvironment->registerObject( 'JobQueueGroup', $mock )` in `ChangePropagationNotifierTest` reaches production.
* Removed the internal `SMW\MediaWiki\MessageBuilder` class along with `MwCollaboratorFactory::newMessageBuilder()`. `MessageBuilder` was a thin convenience wrapper over `Message`, `Language`, and `PagerNavigationBuilder` from MediaWiki core; it added no SMW-domain logic. `HtmlFormRenderer`'s constructor signature changes from `( Title $title, MessageBuilder $messageBuilder )` to `( Title $title, Language $language )` and exposes a `getLanguage()` accessor in place of `getMessageBuilder()`. Internal callers that built cursor-pagination via `MessageBuilder::cursorPrevNextToText()` (`QueryPage`, `SpecialConcepts`, `SpecialTypes`, `ValueListBuilder`) now instantiate `MediaWiki\Navigation\PagerNavigationBuilder` directly; callers that resolved messages via `MessageBuilder::getMessage()` (`HtmlFormRenderer`, `PageBuilder`) now use `wfMessage()->inLanguage( $language )` directly.
* Removed the internal `SMW\MediaWiki\Deferred\HashFieldUpdate` and `SMW\MediaWiki\Deferred\ChangeTitleUpdate` classes. Both were thin orchestration wrappers around `MediaWiki\Deferred\DeferrableUpdate`: `HashFieldUpdate` scheduled a single `smw_hash` rewrite (now inlined as a private `EntityIdFinder::deferHashUpdate()` helper at its two callsites using `DeferredUpdates::addCallableUpdate()`); `ChangeTitleUpdate` deferred post-move re-parse jobs (now inlined directly in `RedirectUpdater::triggerChangeTitleUpdate()`). The static `HashFieldUpdate::$isCommandLineMode` test seam is replaced by the existing `Site::isCommandLineMode()` check at the inlined sites. `RedirectUpdater::triggerChangeTitleUpdate()` is unchanged in signature and continues to resolve `JobFactory` through `ServicesFactory::getInstance()->newJobFactory()` so `$testEnvironment->registerObject( 'JobFactory', $mock )` overrides reach production as before.
* Removed the internal `SMW\Utils\Logger` class along with `ServicesFactory::getMediaWikiLogger()` and the `'MediaWikiLogger'` `singleton()` / `create()` dispatch entry. `Logger` wrapped a PSR-3 logger with a role-based filter (`ROLE_DEVELOPER` / `ROLE_USER` / `ROLE_PRODUCTION`) and a context-value transformer (rounded `procTime` and `time` floats to five decimals, JSON-encoded array context values). In production paths the wrapper was always instantiated as `ROLE_DEVELOPER`, which logs every message regardless of role tag, so the filter was inert. SMW callers (`ElasticFactory`, `SQLStoreFactory`, `QueryEngineFactory`, `MwCollaboratorFactory`, `FactboxFactory`, `EventListenerRegistry`, `ParserCachePurgeJob`, `ChangePropagationDispatchJob`, and others) now resolve their PSR-3 logger directly via `MediaWiki\Logger\LoggerFactory::getInstance( 'smw' )` (or `'smw-elastic'`). `'role' => 'user'` / `'role' => 'production'` context tags that callers still pass become observational metadata only.
* Removed the internal `SMW\MediaWiki\HookDispatcher` class and `SMW\MediaWiki\HookDispatcherAwareTrait`, along with `ServicesFactory::getHookDispatcher()` and the `SMW.HookDispatcher` service. `HookDispatcher` was a thin wrapper over MediaWiki core's `HookContainer` (available since MW 1.35, well below SMW's 1.43 minimum); all SMW-defined hook names and signatures are unchanged, and callers now invoke `MediaWiki\HookContainer\HookContainer::run( 'SMW::X::Y', [ ... ] )` directly. The corresponding `setHookDispatcher( HookDispatcher )` setters on `Settings`, `Setup`, `RevisionGuard`, `ConstraintRegistry`, `SchemaTypes`, `PropertyChangeListener`, `TaskHandlerFactory`, `TaskHandlerRegistry`, `EntityExaminerIndicatorsFactory`, `InTextAnnotationParser`, and `Installer` are renamed to `setHookContainer( HookContainer )` with the corresponding type change. The three `extension.json` HookHandler `services:` arrays that referenced `SMW.HookDispatcher` (`ParserAfterTidy`, `GetPreferences`, `SpecialAdmin`) now reference `HookContainer`. Listener registrations against SMW hook names continue to work unchanged.
* Removed internal `MutedInsertQueryBuilder`, `MutedUpdateQueryBuilder`, `MutedDeleteQueryBuilder`, and `MutedReplaceQueryBuilder` (added briefly in the 7.0.0 development cycle). `Database::new*QueryBuilder()` factories now return MediaWiki core's base types directly. See "Transaction profiler warnings no longer silenced" above for the behaviour change.
* Removed the static `SMW\MediaWiki\LinkBatch::singleton()` and `SMW\MediaWiki\LinkBatch::reset()` methods, along with the dead `LinkBatch::has()` method. The internal wrapper is now the `SMW.LinkBatch` service, reachable via `ServicesFactory::getLinkBatch()` and injected into its consumers (`SMW\SQLStore\EntityStore\PrefetchItemLookup` and `SMW\SQLStore\EntityStore\CacheWarmer`) through the constructor instead of being fetched from a static singleton.
* **`rule-json` content model alias removed.** Pages with the `rule-json` content model (legacy SMW <3.1 schemas) are no longer mapped to `SchemaContentHandler` by the `ContentHandlerForModelID` hook. Migrate any remaining `rule-json` pages to the `smw/schema` content model. The `smw/schema` model itself is now registered declaratively via `ContentHandlers` in `extension.json` (with `Store` injected via the `services` array), and the corresponding hook handler `SMW\MediaWiki\Hooks::onContentHandlerForModelID` has been removed.
* **Legacy job aliases removed.** All job types must now use their `smw.*` names; the old `SMW\…Job` / `SMW…Job` class-name aliases no longer work. See the [migration guide](../migration/7.0.md#removed-job-aliases) for the full alias-to-name mapping.
* **Long-deprecated methods and functions removed.** A batch of methods, functions, constants, and hook names deprecated as far back as SMW 2.1 has been removed (e.g. `smwfNormalTitleText()`, `TimeValue::getXMLSchemaDate()`, `DataValueFactory::newDataItemValue()`, the `smwInitProperties` hook). See the [migration guide](../migration/7.0.md#removed-long-deprecated-methods) for the full list of removed symbols and their replacements.

### Changed

* **`RequestOptions::addExtraCondition` callbacks now receive `SqlFragmentBuilder` instead of `Query`.** The new class exposes the same fragment helpers (`eq`, `neq`, `in`, `like`) and `alias` / `index` properties, so untyped callbacks need no changes. Typed callbacks must update the hint to `SMW\MediaWiki\Connection\SqlFragmentBuilder`. `Query` and `Database::newQuery()` are removed.
* **`SMW::GroupPermissions::BeforeInitializationComplete` hook removed.** Permission rights and group assignments are now declared in `extension.json`. Extensions that modified SMW permissions via this hook should use MediaWiki's standard `$wgGroupPermissions` override in `LocalSettings.php` instead.
* **Four legacy hook names removed.** SMW previously fired these old hook names alongside their modern replacements; they have been deprecated since 2.3-3.1. Extensions that registered handlers under the old names must move them to the modern hook:

  | Removed | Use instead | Deprecated since |
  |---|---|---|
  | `smwRefreshDataJobs` | `SMW::SQLStore::BeforeDataRebuildJobInsert` | 2.3 |
  | `SMWSQLStore3::updateDataAfter` | `SMW::SQLStore::AfterDataUpdateComplete` | 2.3 |
  | `SMWStore::updateDataBefore` | `SMW::Store::BeforeDataUpdateComplete` | 3.1 |
  | `SMWStore::updateDataAfter` | `SMW::Store::AfterDataUpdateComplete` | 3.1 |

  The two `SMW::Store::*` modern hooks accept the same arguments as their predecessors; renaming the registration is sufficient. The two `SMW::SQLStore::*` modern hooks pass different arguments, so handler callback signatures must also be updated: `SMW::SQLStore::BeforeDataRebuildJobInsert` prepends a `Store $store` parameter, and `SMW::SQLStore::AfterDataUpdateComplete` appends a `ChangeOp $changeOp` parameter.

* Removed `EntityIdManager::MAX_CACHE_SIZE`. Cache sizes are now per-pool and exposed as `EntityIdManager::DEFAULT_CACHE_SIZES`, configurable via `$smwgEntityCacheSizes`.
* **`Property::getRedirectTarget()` removed.** Use `Store::getRedirectTarget()` directly; remember to preserve the inverse short-circuit (the removed method returned `$this` when `m_inverse` was true).
* `SMW\MediaWiki\JobFactory::batchInsert()` is now an instance method instead of a static method. Call it on an injected `JobFactory` instance (`$jobFactory->batchInsert( $jobs )`) rather than statically.

### Deprecated

* `$smwgConfigFileDir` is deprecated (since 7.0.0) and will be removed in 8.0.0. Install-state metadata now lives in the `smw_meta` database table (see Action required when upgrading). The setting is kept so the `update.php` migration can find a pre-existing `.smw.json` at a non-default location; it has no further effect once the file has been renamed to `.smw.json.migrated`.
* `ServicesFactory::singleton()` and `ServicesFactory::create()` are deprecated (since 7.0.0). Use the typed accessor and factory methods on `ServicesFactory` directly ([#6428](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6428)). Note: for container-managed services, `create()` no longer guarantees a fresh instance; it is now equivalent to `singleton()` for those services.
* `enableSemantics()` is deprecated and now a no-op. `wfLoadExtension( 'SemanticMediaWiki' )` alone is sufficient to install SMW, aligning with standard MediaWiki extension conventions. The RDF namespace URI is now auto-derived from `Special:URIResolver` when not explicitly set. Users who set a custom `$smwgNamespace` in `LocalSettings.php` are unaffected.
* A batch of class aliases (around 50 old fully-qualified names such as `SMWDIBlob`, `SMWQuery`, and `SMW\SemanticData`) is deprecated and will be removed in a future release. Update any code referencing these to the new namespaced class names. See the [migration guide](../migration/7.0.md#deprecated-class-aliases) for the full alias-to-class mapping.
* Renamed the internal `SMW\SQLStore\ConceptCache` class to `SMW\SQLStore\ConceptMaterializer`, which better describes its role of materializing concept query results into the concept cache table. The old name is retained as a deprecated class alias (since 7.0.0) and will be removed in a future release. The supported public API for concept-cache operations is unchanged: `Store::refreshConceptCache()`, `Store::deleteConceptCache()`, and `Store::getConceptCacheStatus()`.

### Internal improvements

* **DI layer migrated to `ServiceContainer`.** The internal dependency-injection layer now uses MediaWiki's `Wikimedia\Services\ServiceContainer` instead of the third-party `onoi/callback-container`. This is a fully internal change with no effect on public APIs ([#6428](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6428)).
* **Config defaults migrated to `extension.json`.** All `$smwg*` defaults are now declared in the manifest's `config` block (with `merge_strategy` declarations on compound arrays so partial writes from `LocalSettings.php` merge cleanly with defaults — fixes #6649 and the partial-write class behind #6726). Settings whose values can't be expressed as static JSON (PHP constants, `$smwgIP`-relative paths, class constants) are seeded by a small `SMW\Setup\ConfigBootstrap` callback at registration time.
* Native PHP type coverage significantly expanded across the entire codebase, including return types, parameter types, property types, and constructor promotion with `readonly`
* PHPUnit test suite reorganized into `Unit/` and `Integration/` directories
* Numerous static analysis (phan) errors fixed
* Migrated `Special:Browse`'s search form from the deprecated `mediawiki.ui` ResourceLoader modules to Codex CSS-only components, ahead of `mediawiki.ui`'s removal in MediaWiki 1.46 ([#6476](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6476))
* Migrated client-side date rendering off MediaWiki's internal `wgMonthNames` JavaScript config variable to the `mediawiki.language.months` module ([#3851](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3851))
* CI updated: added MediaWiki 1.45 and 1.46, and PHP 8.5, to the test matrix, added cancellation of in-progress runs on new pushes, removed Travis CI leftovers

## Upgrading

**Run `update.php` after upgrading.** This release changes the `smw_hash` column type from `VARBINARY(40)` to `BINARY(20)`. The update script converts existing hash values automatically via a single server-side `UPDATE` before the column-type change. On wikis with millions of entities, expect this UPDATE to hold a write lock on `smw_object_ids` for the duration of the conversion — plan a maintenance window if needed.

**If you use fulltext search** (`smwgEnabledFulltextSearch`): run `rebuildFulltextSearchTable.php` after upgrading to rebuild the index with the new ICU-based transliteration.

**If you set `$smwgNamespaceIndex`** in `LocalSettings.php`: remove that line and replace it with explicit `define()` calls for the six SMW namespace constants, placed BEFORE `wfLoadExtension( 'SemanticMediaWiki' )`. See the `$smwgNamespaceIndex` entry under [Action required when upgrading](#action-required-when-upgrading) above for the snippet. Without this change SMW will refuse to boot.

**If you passed a domain to `enableSemantics()`** (e.g. `enableSemantics( 'example.org' )`): that argument set the RDF namespace URI used in OWL/RDF export, and it no longer has any effect now that `enableSemantics()` is a no-op. Set the RDF namespace URI directly with `$smwgNamespace` in `LocalSettings.php`:

```php
wfLoadExtension( 'SemanticMediaWiki' );
$smwgNamespace = 'http://example.org/id/';
```

When `$smwgNamespace` is a complete URI, the exporter uses it as-is. When it is not set, SMW auto-derives the URI from `Special:URIResolver`. If your exported RDF previously used a custom base URI, set `$smwgNamespace` to that exact URI to keep entity identifiers stable across the upgrade.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 6.0.0, ensure the SMW version in `composer.local.json` is `^7.0.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader`

## See also

* [7.0 migration guide](../migration/7.0.md) for the full reference tables: string-based configuration, removed legacy settings, removed job aliases, deprecated class aliases, and removed long-deprecated methods.
* [Compatibility matrix](../COMPATIBILITY.md#compatibility) for supported PHP and MediaWiki versions.
