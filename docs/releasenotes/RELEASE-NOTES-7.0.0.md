# Semantic MediaWiki 7.0.0

Released on TBD.

This release makes Semantic MediaWiki easier to install and run, brings significant query and indexing performance improvements, and adds MediaWiki 1.45 support. Installation now follows standard MediaWiki conventions, configuration accepts plain strings instead of `SMW_*` constants, and install-state metadata moves into the database. If you maintain an extension or integration that depends on SMW, see [For developers and extension authors](#for-developers-and-extension-authors) and the [migration guide](../migration/7.0.md) for the removed and changed APIs.

## Compatibility

* Added support for MediaWiki 1.45 (new compared to SMW 6.0.x, which supported up to 1.44)
* Compatible with PHP 8.1 up to 8.4 and MediaWiki 1.43 up to 1.45

For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Highlights

Adds MediaWiki 1.45 support (see [Compatibility](#compatibility)).

**Easier to install and run, aligned with MediaWiki conventions.** SMW now uses the standard MediaWiki mechanisms instead of its own bespoke ones.

* [`enableSemantics()` is no longer required](#deprecated): plain `wfLoadExtension( 'SemanticMediaWiki' )` is enough.
* [String-based configuration](#configuration-changes) means no more `SMW_*` constants.
* [Install-state moved from `.smw.json` to the database](#action-required-when-upgrading), so no shared filesystem is needed for multi-server setups.
* [`smw-admin` is granted to `sysop` by default](#new-features-and-enhancements), so admins reach `Special:SemanticMediaWiki` out of the box.
* [Namespaces relocate via standard MediaWiki `define()` constants](#action-required-when-upgrading) instead of `$smwgNamespaceIndex` (MediaWiki core's documented mechanism since 1.30).

**Significant performance improvements.**

* [Query sort speedups](#faster-property-queries) (orders of magnitude on large wikis), `order=none`, and cursor-based pagination.
* [Lazy dependency refresh](#lazy-dependency-refresh) and reduced job queue load on edits and deletes of widely-referenced pages.

**Built on MediaWiki core.**

* [Bundled third-party libraries dropped](#removed) in favor of MediaWiki core services (`Onoi\Tesa`, `Onoi\HttpRequest`, `onoi/callback-container`), and a large batch of long-deprecated APIs removed. Extension authors: see the [migration guide](../migration/7.0.md).

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

### New features and enhancements

* The `smw-admin` right is now granted to the `sysop` group by default, so wiki administrators can reach `Special:SemanticMediaWiki` out of the box without first joining the `smwadministrator` group. The `smwadministrator` group is retained for installs that want SMW administration to be a separate role; revoke the new default with `$wgGroupPermissions['sysop']['smw-admin'] = false;` in `LocalSettings.php`.
* Changed `smw_hash` storage from hex-encoded to raw binary, reducing the hash index size and improving query performance on large wikis. Column type changes from `VARBINARY(40)` to `BINARY(20)` on MySQL/MariaDB and SQLite, and from `TEXT` to `BYTEA` on PostgreSQL. Existing hashes are converted automatically during `update.php`. ([#6587](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6587))
* Improved pagination performance on Special:Properties and Special:UnusedProperties by switching from OFFSET-based to cursor-based pagination. Browsing deep pages is now significantly faster on wikis with many properties. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
  * Navigation links now use `after=` and `before=` URL parameters instead of `offset=`. Existing `offset=` bookmarks continue to work.
  * The numbered result list has been replaced with a bullet list, and the "starting with #N" indicator has been removed, as cursor-based pagination does not track absolute position.
* The `smwbrowse` API (used by `browse=property`, `browse=category`, `browse=concept`) now supports opt-in cursor pagination. Clients that send `cursor` in the request payload receive a `query-continue-cursor` field in the response and walk forward via keyset seeks instead of OFFSET. Old clients that follow `query-continue-offset` continue to work unchanged. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
<a id="faster-property-queries"></a>
* `#ask` queries that sort by a property value are now significantly faster on MariaDB and MySQL. The query engine restructures the SQL so the database can choose a more efficient plan; on large wikis the improvement can be orders of magnitude depending on query shape. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
  * Set `$smwgQUseLegacyQuery = true` in `LocalSettings.php` to fall back to the previous query shape if you encounter a regression after upgrading.
  * A redundant `DISTINCT` keyword was also dropped from the disjunction-query temp-table insert. Same result, less work for the database; no setting required.
* On wikis with many distinct entities per page, SMW's internal caches could fill up during a single render and force repeated database lookups for the same pages. Cache sizes are now adjustable via the new `$smwgEntityCacheSizes` setting. Per-pool hit and miss counts are also emitted to MediaWiki's `StatsFactory` service, so wikis already configured to collect MediaWiki metrics (`$wgStatsTarget` and `$wgStatsFormat`) can see cache effectiveness in their existing dashboards and size caches based on real traffic instead of guessing.
* `#ask` queries no longer fragment the parser cache by the user's date format preference. SMW formats dates by language, not by that preference, so the fragmentation produced no benefit. `dateformat` has been removed from the `$smwgSetParserCacheKeys` default, which now contains only `userlang`.
* `#ask` queries whose result format produces language-neutral output (`json`, `rdf`, `count`, `debug`) no longer fragment the parser cache by `userlang`, unless the query produces errors (error messages are localised). Presentation formats such as `table` and `list`, whose output includes localised text, continue to fragment by `userlang` as before. Result printers can declare their own behaviour by overriding `ResultPrinter::dependsOnUserLanguage()`.
* `#ask` queries now accept `order=none` to skip result sorting entirely. Every `#ask` query previously sorted by page, which on large result sets forces the database to sort the whole intermediate set before applying the limit. With `order=none` the query engine emits no `ORDER BY`, so the limit can short-circuit; on large queries this can be orders of magnitude faster. Results are returned in an unspecified order, and `order=none` cannot be combined with cursor pagination. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
* Parsing pages with many subobjects is now significantly faster. SMW resolved the page content language once for every property value, so a page with hundreds of subobjects triggered thousands of repeated lookups of the same page's language. The result is now memoized per page within a request. ([#6559](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6559))
<a id="lazy-dependency-refresh"></a>
* Property and Category page edits no longer force a re-parse of every dependent when only display-only annotations change (`_SUBC`, `_SUBP`, `_PDESC`, `_PPLB`). On wikis with thousands of dependents the job runner cost drops from hours to seconds. ([#6880](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6880))
* On wikis with `smwgEnabledQueryDependencyLinksStore` enabled, deleting a page no longer queues a forced re-parse of every other page whose `#ask` queries referenced it. Dependents refresh lazily on next view via the existing `DependencyValidator` invalidation path. For deletions of widely-referenced subjects the job runner cost drops to zero at delete time; the parse cost is paid only on view of dependents that are actually visited. ([#6881](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6881))
* On wikis with `smwgEnabledQueryDependencyLinksStore` enabled, viewing a page whose `#ask` query dependencies are stale now triggers exactly one re-parse instead of two. The previous behavior re-parsed once uncached and then re-parsed a second time to populate the parser cache; the new flow uses MediaWiki's normal `RejectParserCacheValue` cache-rejection path so a single fresh parse is saved normally. No configuration change required. ([#6882](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6882))

### Bug fixes

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
* Removed the `@private` internal class `SMW\Services\SharedServicesContainer` and the internal wiring files `src/Services/mediawiki.php`, `src/Services/events.php`, and `src/Services/cache.php`. These were never part of the public API ([#6428](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6428)).
* Removed the root `DefaultSettings.php` shim (deprecated since 4.0.0). Use `$GLOBALS['smwgFoo']` or `SMW\Settings::getInstance()->get('smwgFoo')` instead.
* Removed `Defines.php`.
* **`includes/` directory removed.** All classes have moved to `src/` under new namespaces (`DataItems/`, `DataValues/`, `Export/`, `Formatters/`, `Query/`, `QueryPages/`, `MediaWiki/Specials/`). Class aliases are provided for the transition (see Deprecated below), but code that loaded files by path (e.g., `require .../includes/dataitems/...`) will break.

**Configuration and runtime APIs:**

* **`SMW\SemanticMediaWiki::getDefaultSettings()` and `SMW\SemanticMediaWiki::setupGlobals()` removed.** SMW's defaults now come from `extension.json`'s `config` block (and a small registration callback for constants/paths) instead of the bespoke `src/DefaultSettings.php` array + `setupGlobals()` seeding. External code that called `getDefaultSettings()` should read globals directly via `$GLOBALS['smwgFoo']` or via `SMW\Settings::getInstance()->get('smwgFoo')`.
* **`src/DefaultSettings.php` removed.** Per-setting documentation that previously lived as inline comments in this file now lives at `docs/config.md` (one section per setting) and in the manifest's `description` field. Authoring `LocalSettings.php` is unchanged — `$smwgFoo = …;` continues to work for every setting that ever did.

**Removed APIs:**

* **`browsebyproperty` and `browsebysubject` API modules removed.** Both were deprecated since 3.0. Use the `smwbrowse` API module (`action=smwbrowse`) instead.
* **`SMW\MediaWiki\Api\PropertyListByApiRequest` removed.** This was an internal helper for the now-removed `browsebyproperty` module; no other code consumed it. External consumers should query the `smwbrowse` API module directly.
* **`SMW\MediaWiki\Specials\SpecialPage` base class removed**, along with the legacy `SMW\SpecialPage` alias. All in-tree SMW special pages now extend MediaWiki core's `MediaWiki\SpecialPage\SpecialPage` directly and receive `Store` / `Settings` through their constructor (registered via `SpecialPages` ObjectFactory specs in `extension.json`). Third-party special pages that extended the SMW base class must extend MediaWiki core's `SpecialPage` and inject the services they need.
* **Legacy DML methods on `SMW\MediaWiki\Connection\Database` removed.** Removed methods: `select()`, `selectRow()`, `selectField()`, `estimateRowCount()`, `insert()`, `update()`, `delete()`, `upsert()`, `replace()`, and the `makeSelectOptions()` passthrough. `Database::query()` and `Database::readQuery()` also tightened from `Query|string` to `string` only. SMW's database wrapper is internal infrastructure; external code that called these methods directly on `$store->getConnection( 'mw.db' )` should migrate to MediaWiki core's database services. See [Manual:Database access](https://www.mediawiki.org/wiki/Manual:Database_access).
* Removed `getTextFromContent()`, `replacePrefixes()`, and `textAlreadyUpdatedForIndex()` from `ExtendedSearchEngine`, matching their removal from MediaWiki core's `SearchEngine`.
* Removed unused internal classes: `HtmlVTabs`, `SchemaParameterTypeMismatchException`, `CleanUpTables`, and `FlatSemanticDataSerializer`.
* Removed the internal `SMW\Utils\TemplateEngine` class and its bundled `.ms` templates under `data/template/`. All consumers now render through MediaWiki core's `MediaWiki\Html\TemplateParser` with Mustache templates.
* Removed several internal wrappers around MediaWiki core services. The `SMW\MediaWiki\FileRepoFinder` class (its `findFile()` was a pure `RepoGroup::findFile()` passthrough; `findFromArchive()` had no production callers) and its `ServicesFactory::getFileRepoFinder()` accessor are gone; the single production caller (`SMW\Elastic\Indexer\Attachment\FileHandler`) now takes a `RepoGroup` directly. `SMW\MediaWiki\PageInfoProvider::isProtected()` is removed; callers now use `MediaWikiServices::getInstance()->getRestrictionStore()->isProtected()` directly. The unused `SMW\MediaWiki\RedirectTargetFinder::hasContentHandler()` method is also removed.
* Removed `SMW\MediaWiki\Permission\PermissionExaminer::setUser()`. The class's `User` is always injected at construction time via `ServicesFactory::newPermissionExaminer()`; the lone caller in `GetPreferences::process()` was redundant. Tests should pass the user as the constructor's second argument.
* Removed the internal classes `SMW\MediaWiki\Preference\PreferenceExaminer` and `SMW\MediaWiki\Preference\PreferenceAware`, along with `ServicesFactory::newPreferenceExaminer()`. `PreferenceExaminer` was a thin wrapper over MediaWiki core's `UserOptionsLookup`; callers now use `UserOptionsLookup::getOption()` directly. `PreferenceAware` was an interface with no implementors. Neither was part of the public API.
* Removed the `STAGE_PRESEND` / `STAGE_POSTSEND` constants and the `asPresend()` / `getStage()` methods from `SMW\MediaWiki\Deferred\CallableUpdate`. The stage routing they implemented had zero production callers (only the now-removed unit test exercised them), and the registration path against `DeferredUpdates::addUpdate()` no longer threads a stage argument. All updates default to `POSTSEND` as before.
* Removed the internal `SMW\MediaWiki\ManualEntryLogger` class along with `ServicesFactory::getManualEntryLogger()` and the `SMW.ManualEntryLogger` service. `ManualEntryLogger` wrapped `ManualLogEntry` with a registry of permitted event types; the registry was always satisfied at each callsite, so the gate added no value. Internal callers (`MaintenanceLogger`, `EntityLookupTaskHandler`) now construct `ManualLogEntry` directly. `MaintenanceLogger`'s constructor loses its second `ManualEntryLogger` parameter.
* Removed the internal `SMW\MediaWiki\TitleFactory` class along with `ServicesFactory::newTitleFactory()`, `ServicesFactory::getTitleFactory()`, and the `SMW.TitleFactory` service. `TitleFactory` was a thin wrapper around MediaWiki core's `\MediaWiki\Title\TitleFactory` (available since MW 1.35) plus a `createPage()` / `createFilePage()` delegation to `WikiPage`. Callers now obtain MediaWiki core's `TitleFactory` from `MediaWikiServices::getInstance()->getTitleFactory()` and use `MediaWiki\Page\WikiPageFactory` for `WikiPage` construction; the SMW wrapper's `newFromIDs()` batch-lookup was inlined as a private helper in `SMW\SQLStore\Rebuilder\Rebuilder` (its only caller). `TextContentCreator`'s constructor gains a third `WikiPageFactory` parameter.
* Removed the internal `SMW\MediaWiki\Connection\LoadBalancerConnectionProvider` and `SMW\Connection\CallbackConnectionProvider` classes. Both implemented the `SMW\Connection\ConnectionProvider` interface as thin lazy-init wrappers (one over `ILoadBalancer::getConnection()`, the other over an arbitrary callable). `MwCollaboratorFactory::newLoadBalancerConnectionProvider()` now returns an anonymous `ConnectionProvider` implementation directly; the return type changes from `LoadBalancerConnectionProvider` to `SMW\Connection\ConnectionProvider`. `ConnectionManager::registerCallbackConnection()` is unchanged in behaviour. Code that type-hinted parameters or property declarations against the concrete `LoadBalancerConnectionProvider` class must switch to the `SMW\Connection\ConnectionProvider` interface; references to `CallbackConnectionProvider` were undocumented and had no callers outside the wrapper itself.
* Removed nine pure-delegation accessor methods on `SMW\Services\ServicesFactory`: `getLoadBalancer()`, `getDBLoadBalancer()`, `getDBLoadBalancerFactory()`, `getMainConfig()`, `getSearchEngineConfig()`, `getMagicWordFactory()`, `getContentLanguage()`, `getParserCache()`, and `getUserOptionsLookup()`. Each was a thin pass-through to the corresponding `MediaWikiServices::getInstance()->getX()` accessor with a testOverrides hook that no test consumed. SMW now resolves these MediaWiki core services through `MediaWikiServices` directly. The matching `'DBLoadBalancer'`, `'DBLoadBalancerFactory'`, `'MainConfig'`, `'SearchEngineConfig'`, `'MagicWordFactory'`, `'ContentLanguage'`, `'ParserCache'`, and `'UserOptionsLookup'` entries on the `ServicesFactory::singleton()` / `ServicesFactory::create()` dispatch table are removed too, as is the unused `SMW.SearchEngineConfig` service wiring. `ServicesFactory::getJobQueueGroup()` and its `'JobQueueGroup'` dispatch entry are retained because the `SMW.JobQueue` closure in `ServiceWiring.php` resolves the inner `JobQueueGroup` through it so `$testEnvironment->registerObject( 'JobQueueGroup', $mock )` in `ChangePropagationNotifierTest` reaches production.
* Removed the internal `SMW\MediaWiki\MessageBuilder` class along with `MwCollaboratorFactory::newMessageBuilder()`. `MessageBuilder` was a thin convenience wrapper over `Message`, `Language`, and `PagerNavigationBuilder` from MediaWiki core; it added no SMW-domain logic. `HtmlFormRenderer`'s constructor signature changes from `( Title $title, MessageBuilder $messageBuilder )` to `( Title $title, Language $language )` and exposes a `getLanguage()` accessor in place of `getMessageBuilder()`. Internal callers that built cursor-pagination via `MessageBuilder::cursorPrevNextToText()` (`QueryPage`, `SpecialConcepts`, `SpecialTypes`, `ValueListBuilder`) now instantiate `MediaWiki\Navigation\PagerNavigationBuilder` directly; callers that resolved messages via `MessageBuilder::getMessage()` (`HtmlFormRenderer`, `PageBuilder`) now use `wfMessage()->inLanguage( $language )` directly.
* Removed the internal `SMW\MediaWiki\Deferred\HashFieldUpdate` and `SMW\MediaWiki\Deferred\ChangeTitleUpdate` classes. Both were thin orchestration wrappers around `MediaWiki\Deferred\DeferrableUpdate`: `HashFieldUpdate` scheduled a single `smw_hash` rewrite (now inlined as a private `EntityIdFinder::deferHashUpdate()` helper at its two callsites using `DeferredUpdates::addCallableUpdate()`); `ChangeTitleUpdate` deferred post-move re-parse jobs (now inlined directly in `RedirectUpdater::triggerChangeTitleUpdate()`). The static `HashFieldUpdate::$isCommandLineMode` test seam is replaced by the existing `Site::isCommandLineMode()` check at the inlined sites. `RedirectUpdater::triggerChangeTitleUpdate()` is unchanged in signature and continues to resolve `JobFactory` through `ServicesFactory::getInstance()->newJobFactory()` so `$testEnvironment->registerObject( 'JobFactory', $mock )` overrides reach production as before.
* Removed the internal `SMW\Utils\Logger` class along with `ServicesFactory::getMediaWikiLogger()` and the `'MediaWikiLogger'` `singleton()` / `create()` dispatch entry. `Logger` wrapped a PSR-3 logger with a role-based filter (`ROLE_DEVELOPER` / `ROLE_USER` / `ROLE_PRODUCTION`) and a context-value transformer (rounded `procTime` and `time` floats to five decimals, JSON-encoded array context values). In production paths the wrapper was always instantiated as `ROLE_DEVELOPER`, which logs every message regardless of role tag, so the filter was inert. SMW callers (`ElasticFactory`, `SQLStoreFactory`, `QueryEngineFactory`, `MwCollaboratorFactory`, `FactboxFactory`, `EventListenerRegistry`, `ParserCachePurgeJob`, `ChangePropagationDispatchJob`, and others) now resolve their PSR-3 logger directly via `MediaWiki\Logger\LoggerFactory::getInstance( 'smw' )` (or `'smw-elastic'`). `'role' => 'user'` / `'role' => 'production'` context tags that callers still pass become observational metadata only.
* Removed the internal `SMW\MediaWiki\HookDispatcher` class and `SMW\MediaWiki\HookDispatcherAwareTrait`, along with `ServicesFactory::getHookDispatcher()` and the `SMW.HookDispatcher` service. `HookDispatcher` was a thin wrapper over MediaWiki core's `HookContainer` (available since MW 1.35, well below SMW's 1.43 minimum); all SMW-defined hook names and signatures are unchanged, and callers now invoke `MediaWiki\HookContainer\HookContainer::run( 'SMW::X::Y', [ ... ] )` directly. The corresponding `setHookDispatcher( HookDispatcher )` setters on `Settings`, `Setup`, `RevisionGuard`, `ConstraintRegistry`, `SchemaTypes`, `PropertyChangeListener`, `TaskHandlerFactory`, `TaskHandlerRegistry`, `EntityExaminerIndicatorsFactory`, `InTextAnnotationParser`, and `Installer` are renamed to `setHookContainer( HookContainer )` with the corresponding type change. The three `extension.json` HookHandler `services:` arrays that referenced `SMW.HookDispatcher` (`ParserAfterTidy`, `GetPreferences`, `SpecialAdmin`) now reference `HookContainer`. Listener registrations against SMW hook names continue to work unchanged.
* Removed internal `MutedInsertQueryBuilder`, `MutedUpdateQueryBuilder`, `MutedDeleteQueryBuilder`, and `MutedReplaceQueryBuilder` (added briefly in the 7.0.0 development cycle). `Database::new*QueryBuilder()` factories now return MediaWiki core's base types directly. See "Transaction profiler warnings no longer silenced" above for the behaviour change.
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

### Deprecated

* `$smwgConfigFileDir` is deprecated (since 7.0.0) and will be removed in 8.0.0. Install-state metadata now lives in the `smw_meta` database table (see Action required when upgrading). The setting is kept so the `update.php` migration can find a pre-existing `.smw.json` at a non-default location; it has no further effect once the file has been renamed to `.smw.json.migrated`.
* `ServicesFactory::singleton()` and `ServicesFactory::create()` are deprecated (since 7.0.0). Use the typed accessor and factory methods on `ServicesFactory` directly ([#6428](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6428)). Note: for container-managed services, `create()` no longer guarantees a fresh instance; it is now equivalent to `singleton()` for those services.
* `enableSemantics()` is deprecated and now a no-op. `wfLoadExtension( 'SemanticMediaWiki' )` alone is sufficient to install SMW, aligning with standard MediaWiki extension conventions. The RDF namespace URI is now auto-derived from `Special:URIResolver` when not explicitly set. Users who set a custom `$smwgNamespace` in `LocalSettings.php` are unaffected.
* A batch of class aliases (around 50 old fully-qualified names such as `SMWDIBlob`, `SMWQuery`, and `SMW\SemanticData`) is deprecated and will be removed in a future release. Update any code referencing these to the new namespaced class names. See the [migration guide](../migration/7.0.md#deprecated-class-aliases) for the full alias-to-class mapping.

### Internal improvements

* **DI layer migrated to `ServiceContainer`.** The internal dependency-injection layer now uses MediaWiki's `Wikimedia\Services\ServiceContainer` instead of the third-party `onoi/callback-container`. This is a fully internal change with no effect on public APIs ([#6428](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/6428)).
* **Config defaults migrated to `extension.json`.** All `$smwg*` defaults are now declared in the manifest's `config` block (with `merge_strategy` declarations on compound arrays so partial writes from `LocalSettings.php` merge cleanly with defaults — fixes #6649 and the partial-write class behind #6726). Settings whose values can't be expressed as static JSON (PHP constants, `$smwgIP`-relative paths, class constants) are seeded by a small `SMW\Setup\ConfigBootstrap` callback at registration time.
* Native PHP type coverage significantly expanded across the entire codebase, including return types, parameter types, property types, and constructor promotion with `readonly`
* PHPUnit test suite reorganized into `Unit/` and `Integration/` directories
* Numerous static analysis (phan) errors fixed
* Migrated `Special:Browse`'s search form from the deprecated `mediawiki.ui` ResourceLoader modules to Codex CSS-only components, ahead of `mediawiki.ui`'s removal in MediaWiki 1.46 ([#6476](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6476))
* CI updated: added MediaWiki 1.45 to the test matrix, added cancellation of in-progress runs on new pushes, removed Travis CI leftovers

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
