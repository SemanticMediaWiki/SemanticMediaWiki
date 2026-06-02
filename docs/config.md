# SMW configuration defaults

Authoritative defaults for every `$smwg*` setting live in
[`extension.json`](../extension.json)'s `config` block. This file is the
human-readable reference: each setting from the manifest gets a section
here covering its purpose, default value, and override semantics.

## Format

```
## $smwg<Name>

One-paragraph description of what the setting does.

**Since:** <version>
**Default:** `<value or pointer>`
**Related:** [optional links to subsystem docs or upstream references]
```

## Subsystem-specific docs

Settings owned by a specific subsystem also have dedicated documentation
beyond the per-setting boxes here:

- **Elasticsearch / ElasticStore** —
  [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md)
- **Importer / vocabularies** —
  [`src/Importer/README.md`](../src/Importer/README.md)

---

## $smwgAdminFeatures

Array of Special:SemanticMediaWiki admin-panel features and alerts. Each
entry enables a distinct panel section or maintenance alert.

- `'refresh'`, enables the "Refresh/rebuild data" action to initiate
  repairing or updating all wiki data.
- `'setup'`, allows running database installation and upgrade from the
  admin panel.
- `'disposal'`, allows access to the "Object ID lookup and disposal"
  feature and the "Outdated entities disposal" tool.
- `'pstats'`, allows updating property statistics.
- `'fullt'`, allows rebuilding the fulltext search index.
- `'maintenance-script-docs'`, shows the maintenance scripts documentation tab.
- `'show-overview'`, shows the Overview tab.
- `'alert-last-optimization-run'`, shows a maintenance alert when table
  optimization is overdue.

**Since:** 2.5
**Default:** `[ 'refresh', 'setup', 'disposal', 'pstats', 'fullt', 'maintenance-script-docs', 'show-overview', 'alert-last-optimization-run' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'refresh'` | `SMW_ADM_REFRESH` |
| `'setup'` | `SMW_ADM_SETUP` |
| `'disposal'` | `SMW_ADM_DISPOSAL` |
| `'pstats'` | `SMW_ADM_PSTATS` |
| `'fullt'` | `SMW_ADM_FULLT` |
| `'maintenance-script-docs'` | `SMW_ADM_MAINTENANCE_SCRIPT_DOCS` |
| `'show-overview'` | `SMW_ADM_SHOW_OVERVIEW` |
| `'alert-last-optimization-run'` | `SMW_ADM_ALERT_LAST_OPTIMIZATION_RUN` |

## $smwgAllowRecursiveExport

When `true`, normal users can request recursive OWL/RDF exports.

**Since:** 0.7
**Default:** `false`

## $smwgAutoRefreshOnPageMove

When `true`, refreshes semantic data in the store when a page is moved.

**Requires:** `$smwgMainCacheType` to be set.

**Since:** 1.9
**Default:** `true`

## $smwgAutoRefreshOnPurge

When `true`, refreshes semantic data in the store when a page is manually purged.

**Requires:** `$smwgMainCacheType` to be set.

**Since:** 1.9
**Default:** `true`

## $smwgAutoRefreshSubject

When `true`, refreshes the semantic store for pages that are edited.

**Since:** 1.5.6
**Default:** `true`

## $smwgBrowseFeatures

Array of Special:Browse capabilities enabled by default.

- `'toolbox-link'`, shows a toolbox link on every content page pointing
  to Special:Browse for that page (replaces `smwgToolboxBrowseLink`).
- `'show-inverse'`, shows incoming links via their inverse properties
  rather than on the "incoming" side (replaces `smwgBrowseShowInverse`).
- `'show-incoming'`, always shows incoming links and expands the
  incoming value list (replaces `smwgBrowseShowAll`).
- `'show-group'`, creates group sections for properties that belong to
  the same property group.
- `'show-sortkey'`, displays the sortkey in the browse view.
- `'use-api'`, generates the browse display via an API request rather
  than inline rendering (replaces `smwgBrowseByApi`).

**Since:** 3.0
**Default:** `[ 'toolbox-link', 'show-incoming', 'show-group', 'use-api' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'toolbox-link'` | `SMW_BROWSE_TLINK` |
| `'show-inverse'` | `SMW_BROWSE_SHOW_INVERSE` |
| `'show-incoming'` | `SMW_BROWSE_SHOW_INCOMING` |
| `'show-group'` | `SMW_BROWSE_SHOW_GROUP` |
| `'show-sortkey'` | `SMW_BROWSE_SHOW_SORTKEY` |
| `'use-api'` | `SMW_BROWSE_USE_API` |

## $smwgCacheUsage

Defines time-to-live (in seconds) for each named cache instance used by Semantic MediaWiki. Requires `$smwgMainCacheType` to be set; without a persistent cache backend the TTL values have no effect.

Set any key to `false` to disable caching for that lookup entirely. Each key is independent — override only the entries you want to tune.

- `special.wantedproperties` — TTL for the lookup powering Special:WantedProperties.
- `special.unusedproperties` — TTL for the lookup powering Special:UnusedProperties.
- `special.properties` — TTL for the property-usage lookup powering Special:Properties.
- `special.statistics` — TTL for the statistics lookup powering Special:Statistics.
- `table.statistics` — TTL for the table-level statistics used internally.
- `api.browse` — TTL for the general `wbgetentities`-style browse API response.
- `api.browse.pvalue` — TTL for browse API responses that return property values.
- `api.browse.psubject` — TTL for browse API responses that return property subjects.
- `api.task` — TTL for API task module responses.
- `api.table.statistics` — TTL for the API table-statistics endpoint.

**Since:** 1.9
**Default:**

```php
[
    'special.wantedproperties' => 3600,
    'special.unusedproperties' => 3600,
    'special.properties'       => 3600,
    'special.statistics'       => 3600,
    'table.statistics'         => 3600,
    'api.browse'               => 3600,
    'api.browse.pvalue'        => 3600,
    'api.browse.psubject'      => 3600,
    'api.task'                 => 3600,
    'api.table.statistics'     => 3600,
]
```

## $smwgCategoryFeatures

Array of category processing features (redirect resolution,
category-as-instance, subcategory-as-hierarchy).

- `'redirect'`, resolves redirects and errors in connection with
  categories.
- `'instance'`, treats category pages that carry `[[Category:Foo]]` as
  elements of category Foo. If disabled, category pages cannot be
  members of other categories. See also `'hierarchy'` (replaces
  `smwgCategoriesAsInstances`).
- `'hierarchy'`, treats subcategories as hierarchy elements: they are
  interpreted as subclasses and automatically annotated with
  `Subcategory of` (replaces `smwgUseCategoryHierarchy`).

**Since:** 3.0
**Default:** `[ 'redirect', 'instance', 'hierarchy' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'redirect'` | `SMW_CAT_REDIRECT` |
| `'instance'` | `SMW_CAT_INSTANCE` |
| `'hierarchy'` | `SMW_CAT_HIERARCHY` |

## $smwgChangePropagationProtection

When `true`, protects an active change propagation from being disabled
without administrative intervention.

**Since:** 3.0
**Default:** `true`

## $smwgChangePropagationWatchlist

Property IDs that trigger full re-processing of dependent pages when their
values change. For example, if `_PVAL` (Allows value) changes for a
property, all pages using that property must be reprocessed.

This setting is not normally changed by users; it is extended by extensions
that add new types with their own declaration properties.

**Since:** 1.5
**Default:** `["_PVAL", "_LIST", "_PVAP", "_PVUC", "_PDESC", "_PPLB", "_PREC", "_PDESC", "_SUBP", "_SUBC", "_PVALI"]`

## $smwgCheckForConstraintErrors

Scope of constraint-error lookups shown via the page indicator. The
constraint error lookup is cached, so no negative performance impact is
expected when viewing a page repeatedly.

- `false`, disables the check and indicator display.
- `'check/main'`, checks only the main subject.
- `'check/all'`, checks the main subject and all subobjects attached to
  it.

**Since:** 3.1
**Default:** `'check/all'`

### Legacy constants

The `SMW_CONSTRAINT_ERR_CHECK_*` constants resolve to these string
values and continue to work without a deprecation notice. The mapping is:

| Value | Legacy constant |
|---|---|
| `false` | `SMW_CONSTRAINT_ERR_CHECK_NONE` |
| `'check/main'` | `SMW_CONSTRAINT_ERR_CHECK_MAIN` |
| `'check/all'` | `SMW_CONSTRAINT_ERR_CHECK_ALL` |

## $smwgCheckForRemnantEntities

Controls when remnant entity checks run. Remnant entities (assignments in
property tables without a corresponding `smw_proptable_hash` entry) rarely
occur but can be left behind after interrupted updates. This setting
controls whether the updater runs additional queries to detect and remove
them.

- `"purge"` — run the check only during a purge action, limiting the
  performance impact to a single subject request.
- `true` — run the check on every update (performance impact unknown).
- `false` — disable the check entirely.

**Since:** 3.1
**Default:** `"purge"`

## $smwgConfigFileDir

Directory that historically held the legacy `.smw.json` and
`.smw.maintenance.json` files. As of SMW 7.0.0, install-state metadata
is stored in the `smw_meta` database table. This setting is now only
consulted by the one-shot migration that runs during `update.php`: if
a pre-existing `.smw.json` file is found at this location, its entries
are imported into `smw_meta` and the file is renamed to
`.smw.json.migrated`. Sites that previously overrode this setting (for
example to `$wgUploadDirectory`) should keep the override in place
until `update.php` has run once.

**Since:** 3.0
**Deprecated since:** 7.0.0; will be removed in 8.0.0.
**Default:** the extension's root directory

## $smwgCompactLinkSupport

When `true`, encodes and compresses Special:Browse / Special:Ask /
Special:SearchByProperty links to reduce URL length.

The generated compact link has no cryptographic security relevance; it is
purely for compactness, not for short-URL shortening. Not expected to be
used as a short-URL service.

**Since:** 3.0
**Default:** `false`

## $smwgCreateProtectionRight

User right required to create new properties; `false` disables creation
protection. When enabled, users can still annotate values using existing
properties, but creating a new property (or modifying its specification)
requires the named right.

**Since:** 3.0
**Default:** `false`

## $smwgDataTypePropertyExemptionList

DataTypes exempted from the automatic corresponding-property registration.
By default, DataTypes such as Date and URL are registered with a
corresponding property of the same name. List DataTypes here to suppress
that automatic registration.

**Since:** 2.5
**Default:** `["Record", "Reference", "Keyword"]`

## $smwgDefaultLoggerRole

Logging granularity role for SMW events; controls which events are written
to the debug log. Logging only occurs when `$wgDebugLogFile` or
`$wgDebugLogGroups` is configured.

- `developer` — output every loggable event produced by SMW.
- `user` — output events deemed important for non-developer operators.
- `production` — output a minimal set of events.

**Since:** 3.0
**Default:** `"production"`

## $smwgDefaultNumRecurringEvents

Default number of recurring-event instances generated when no end date is set.

**Since:** 1.4.3
**Default:** `100`

## $smwgDefaultOutputFormatters

Default output formatter overrides keyed by type ID, type name, or property
name. Only valid formatters are applied; invalid entries are silently
ignored. The formatter is also applied to values displayed on special pages.

Expected key forms:

- `_typeID` — e.g. `'_dat' => 'LOCL'`
- `typeName` — e.g. `'Boolean' => 'tick'`
- `propertyName` — e.g. `'Has date' => 'LOCL'`

**Since:** 3.0
**Default:** `[]`

## $smwgDefaultStore

Default storage backend class; `SQLStore::class` evaluates to this
fully-qualified string at compile time.

**Since:** 0.7
**Default:** `"SMW\\SQLStore\\SQLStore"`
**Related:** See [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md) for ElasticStore setup.

## $smwgDeprecationNotices

Registry of deprecation notices shown in the Special:Admin "Deprecation
notices" panel. Extensions populate this array (keyed by section name) to
inform administrators about settings or features planned for removal or
already removed in the running version.

**Since:** 3.2.0
**Default:** `[]`

## $smwgDetectOutdatedData

When `true`, verifies semantic data is up to date with the latest revision
on every page load, showing a notice in the "entity issue panel" if a
discrepancy is detected. Enabling this increases the frequency with which
the entity issue panel appears on pages.

**Since:** 3.2
**Default:** `false`

## $smwgDir

Alias for `$smwgIP`; resolves to the extension root directory. Provided
for readability in code that does not use `$smwgIP` directly.

**Since:** 1.0
**Default:** the extension's root directory

## $smwgDVFeatures

Array of DataValue type-specific features enabled by default.

- `'provider-redirect'`, (PropertyValue) follows property redirects (Foo to
  Bar) automatically so that query results are equivalent for both
  names. Mainly provided to restore backwards compatibility; enabling
  it is recommended for better user experience.
- `'monolingual-langcode'`, (MonolingualTextValue) requires a language
  code for the value to be considered complete.
- `'pattern-validation'`, allows regular-expression pattern matching when
  an `Allows pattern` property is assigned to a user-defined property.
- `'wpv-display-title'`, (WikiPageValue) looks up a display title and
  uses it as caption when present.
- `'provider-display-title'`, (PropertyValue) resolves a property label by
  matching it against a `Display title of` annotation. Disabled by
  default due to an uncached lookup that may impact performance.
- `'unique-constraint'`, (Uniqueness constraint) allows specifying that a
  property may only hold values with a unique literal representation.
- `'time-calendar-model'`, (TimeValue) indicates the calendar model when
  it is not Gregorian.
- `'number-value-usespaces'`, (Number/QuantityValue) preserves spaces
  within unit labels.
- `'preferred-label'`, supports the use of preferred property labels.
- `'provider-link-hint'`, (PropertyValue) outputs a `<sup>p</sup>` hint
  marker on properties that use a preferred label.
- `'wpv-pipetrick'`, (WikiPageValue) uses a full pipe trick when
  rendering its caption.

**Since:** 2.4
**Default:** `[ 'provider-redirect', 'monolingual-langcode', 'pattern-validation', 'wpv-display-title', 'time-calendar-model', 'preferred-label', 'provider-link-hint' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'provider-redirect'` | `SMW_DV_PROV_REDI` |
| `'monolingual-langcode'` | `SMW_DV_MLTV_LCODE` |
| `'pattern-validation'` | `SMW_DV_PVAP` |
| `'wpv-display-title'` | `SMW_DV_WPV_DTITLE` |
| `'provider-display-title'` | `SMW_DV_PROV_DTITLE` |
| `'unique-constraint'` | `SMW_DV_PVUC` |
| `'time-calendar-model'` | `SMW_DV_TIMEV_CM` |
| `'number-value-usespaces'` | `SMW_DV_NUMV_USPACE` |
| `'preferred-label'` | `SMW_DV_PPLB` |
| `'provider-link-hint'` | `SMW_DV_PROV_LHNT` |
| `'wpv-pipetrick'` | `SMW_DV_WPV_PIPETRICK` |

## $smwgEditProtectionRight

User right required to edit pages protected via `Is edit protected`; `false`
disables the feature. Once a page is annotated with
`[[Is edit protected::true]]`, only users with the named right can edit or
revoke the restriction. The dedicated right `smw-pageedit` is available to
keep this distinct from standard MediaWiki edit protections.

**Since:** 2.5
**Default:** `false`

## $smwgElasticsearchConfig

Top-level configuration map for the ElasticStore: index definition paths, connection parameters, index settings, indexer behaviour, and query-engine tuning. Merged with `array_replace_recursive` semantics so a partial user override (e.g. setting only `query.highlight.fragment.type`) leaves all unset keys at every depth intact.

The setting is organised into five top-level subkeys:

- `index_def` — paths to the JSON files that define the Elasticsearch index mappings for the `data` and `lookup` indices.
- `connection` — low-level HTTP client parameters: `quick_ping` (boolean health-check on connect), `retries` (retry count on failure), `timeout`, and `connect_timeout` (both in seconds).
- `settings` — Elasticsearch index-level settings applied at index creation time (e.g. `index.mapping.total_fields.limit`, `index.max_result_window`).
- `indexer` — controls indexer behaviour: `raw.text` (index raw wikitext), `experimental.file.ingest` (file content ingestion), retry counts for jobs, entity-replication monitoring, and SQLStore compatibility mode.
- `query` — controls query-engine behaviour: connection-failure fallback, profiling, debug output, value-length cap, compatibility mode, subquery size, and highlight fragment parameters.

See the related documentation for per-key details.

**Since:** 3.0
**Default:**

```php
[
    'index_def' => [
        'data'   => $smwgIP . '/data/elastic/smw-data-standard.json',
        'lookup' => $smwgIP . '/data/elastic/smw-lookup.json',
    ],
    'connection' => [
        'quick_ping'      => true,
        'retries'         => 2,
        'timeout'         => 30,
        'connect_timeout' => 30,
    ],
    'settings' => [
        'data' => [
            'index.mapping.total_fields.limit' => 9000,
            'index.max_result_window'          => 50000,
        ],
    ],
    'indexer' => [
        'raw.text'                                  => false,
        'experimental.file.ingest'                  => false,
        'throw.exception.on.illegal.argument.error' => true,
        'job.recovery.retries'                      => 5,
        'job.file.ingest.retries'                   => 3,
        'monitor.entity.replication'                => true,
        'monitor.entity.replication.cache_lifetime' => 3600,
        'data.sqlstore_compatibility'               => true,
        // ...
    ],
    'query' => [
        'fallback.no_connection'          => false,
        'profiling'                       => false,
        'debug.explain'                   => true,
        'debug.description.log'           => true,
        'maximum.value.length'            => 500,
        'compat.mode'                     => true,
        'subquery.size'                   => 10000,
        'highlight.fragment'              => [ 'number' => 1, 'size' => 250, 'type' => false ],
        // ...
    ],
]
```

**Related:** See [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md) for the full key reference.

## $smwgElasticsearchCredentials

ElasticSearch HTTP basic-authentication credentials (`user`/`pass`).

**Since:** 4.2
**Default:** `[]`
**Related:** See [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md).

## $smwgElasticsearchEndpoints

ElasticSearch node endpoint definitions (host/port/scheme objects or
`host:port` strings).

**Since:** 3.0
**Default:** `[]`
**Related:** See [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md).

## $smwgElasticsearchProfile

Path to a JSON profile that overrides `smwgElasticsearchConfig` values;
`false` disables profile loading. Profile values are merged with (and
override) the defaults in `smwgElasticsearchConfig`.

**Since:** 3.0
**Default:** `false`
**Related:** See [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md).

## $smwgEnabledDeferredUpdate

When `true`, defers store updates via MediaWiki's `DeferredUpdates`
mechanism, improving page responsiveness for purge and move actions
significantly. Introduced to align with MediaWiki 1.26+ behaviour where
many update operations already use `DeferredUpdates`.

**Since:** 2.4
**Default:** `true`

## $smwgEnabledEditPageHelp

When `true`, displays SMW help information on the edit page to support
users unfamiliar with SMW.

**Since:** 2.1
**Default:** `false`

## $smwgEnabledFulltextSearch

When `true`, stores text in a separate fulltext-indexed table for SQL
fulltext operations. Tested with MySQL/MariaDB and SQLite.

**Since:** 2.5
**Default:** `false`

## $smwgEnabledQueryDependencyLinksStore

When `true`, stores query dependencies to enable parser-cache invalidation
when queried entities change. It is recommended to also enable
`$smwgQueryResultCacheType` to benefit from automatic query result cache
eviction.

**Since:** 2.3
**Default:** `false`

## $smwgEnabledSpecialPage

Special pages on which SMW annotation and content processing is enabled.

**Since:** 1.9
**Default:** `["Ask"]`

## $smwgEnableExportRDFLink

When `true`, adds a `Special:ExportRDF` `<link>` element to every page
`<head>`. Disabling this prevents bots from discovering and repeatedly
scraping the ExportRDF endpoint, which is not a cheap call.

**Since:** 5.0
**Default:** `true`

## $smwgEnableUpdateJobs

When `false`, disables MediaWiki job-queue updates triggered by semantic
data changes. Disabling this causes some semantic data to become out of
date; manual use of `SMW_refreshData.php` or periodic maintenance scripts
may be needed. Check `Special:Statistics` or `showJobs.php` to assess
queue size before disabling.

**Since:** 1.1.2
**Default:** `true`

## $smwgEntityCollation

Collation strategy for entity sort values. This must correspond to
`$wgCategoryCollation` (same argument values). The setting is global and
applies to all entities in the wiki — it cannot be applied selectively
per-query because the `smw_sort` field stores a pre-computed sort value.

Any change to this setting requires running the `updateEntityCollation.php`
maintenance script.

**Since:** 3.0
**Default:** `"identity"`

## $smwgEntityCacheSizes

Per-pool entry limits for the in-memory caches SMW uses to look up entity IDs during a single request. Each pool maps a string key to an integer capacity. Override individual pools to tune memory use without replacing the full map; pools not listed keep their defaults.

These caches avoid duplicate database queries for the same titles and IDs while a page renders. On large or unusually rich pages the default sizes can fill up and force SMW to re-query entities it has already seen. Raising a limit keeps more entries resident at the cost of additional memory.

Pools:

- `entity.id` — title → SMW internal ID.
- `entity.sort` — title → sortkey.
- `entity.lookup` — SMW ID → WikiPage data item.
- `propertytable.hash` — which property tables hold data for each entity.
- `warmup.byid` — IDs already prefetched in the current request.
- `sequence.map` — SMW ID → property sequence map.
- `redirect.source.lookup` / `redirect.target.lookup` — redirect resolution caches.
- `count.map` — SMW ID → auxiliary count map.

To tune a single pool without replacing the full map, override only that key:

```php
$smwgEntityCacheSizes['entity.id'] = 5000;
```

To assess whether tuning is needed, monitor the `mediawiki.SemanticMediaWiki.inmemory_cache_hits_total` and `mediawiki.SemanticMediaWiki.inmemory_cache_misses_total` metrics emitted via MediaWiki's StatsFactory. A consistently low hit ratio on a specific pool indicates it would benefit from a higher limit.

**Since:** 7.0.0
**Default:** `EntityIdManager::DEFAULT_CACHE_SIZES`

## $smwgExperimentalFeatures

Array of experimental features that can be toggled off to revert to a
previous working state without hot-patching. After a sufficient
in-production period, features are promoted to permanently enabled and
the flag retired.

- `'queryresult-prefetch'`, uses the prefetch method to retrieve
  row-related items for a `QueryResult`.
- `'showparser-curtailment'`, for `#show`, bypasses the `QueryEngine`
  and accesses the DB directly, since `#show` always requests output
  for exactly one entity.

**Since:** 3.0
**Default:** `[ 'queryresult-prefetch', 'showparser-curtailment' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'queryresult-prefetch'` | `SMW_QUERYRESULT_PREFETCH` |
| `'showparser-curtailment'` | `SMW_SHOWPARSER_USE_CURTAILMENT` |

## $smwgExportBacklinks

When `true`, backlinks are included by default in OWL/RDF exports.

**Since:** 0.7
**Default:** `true`

## $smwgExportBCAuxiliaryUse

BC: when `true`, retains the legacy `aux`-marker URIs in RDF/Turtle exports.
The `aux` marker was historically used for selected properties to generate
a helper value; it is now only expected for `_dat` / `_geo` type values.
Any property that does not explicitly need an auxiliary value now uses its
native condition descriptor (`Has_subobject` instead of
`Has_subobject-23aux`).

SPARQL repository users who do not want to run `rebuildData.php` should
keep this set to `true`. This BC setting is planned for removal.

**Since:** 2.3
**Default:** `false`

## $smwgExportBCNonCanonicalFormUse

BC: when `true`, uses localized rather than canonical identifiers in
RDF/Query statements. The preferred form is canonical identifiers
(`Category:`, `Property:`) which ensure RDF/Query statements remain
language-agnostic and continue to work after a site/content language change.

This BC setting is planned for removal.

**Since:** 2.3
**Default:** `false`

## $smwgExportResourcesAsIri

When `true`, resources are exported as IRIs (RFC 3987) instead of
ASCII-encoded URIs. See also the W3C RDF 1.1 specification on IRIs.

**Since:** 2.5
**Default:** `true`

## $smwgExtraneousLanguageFileDir

Directory containing SMW-specific i18n files for extraneous language data
(unit labels, property aliases, and similar locale-specific content).

**Since:** 2.5
**Default:** `<smwgIP>/i18n/extra`

## $smwgFactboxFeatures

Array of factbox capabilities (caching, purge-refresh, subobject
display, attachment display).

- `'cache'`, uses the main cache to avoid reparsing the factbox
  content on each page view (replaces `smwgFactboxUseCache`).
- `'purge-refresh'`, refreshes the factbox content on the purge event
  (replaces `smwgFactboxCacheRefreshOnPurge`).
- `'display-subobject'`, displays subobject references in the factbox.
- `'display-attachment'`, displays the attachment list in the factbox.

**Since:** 3.0
**Default:** `[ 'cache', 'purge-refresh', 'display-subobject', 'display-attachment' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'cache'` | `SMW_FACTBOX_CACHE` |
| `'purge-refresh'` | `SMW_FACTBOX_PURGE_REFRESH` |
| `'display-subobject'` | `SMW_FACTBOX_DISPLAY_SUBOBJECT` |
| `'display-attachment'` | `SMW_FACTBOX_DISPLAY_ATTACHMENT` |

## $smwgFallbackSearchType

Search engine class to fall back to when SMW\MediaWiki\Search\ExtendedSearchEngine cannot parse a query;
`null` uses the database default (e.g. `SearchMySQL`, `SearchPostgres`, or
`SearchOracle`). Set to a fully-qualified class name to override with a
custom search engine.

**Since:** 2.1
**Default:** `null`

## $smwgFieldTypeFeatures

SQLStore field-type modifications. Accepts `false` (disabled, no
field-type registration at all) or an array of any of the values
below. Note that `[]` registers the component but enables no flags; use
`false` to skip registration entirely.

- `'char-nocase'`, switches selected search fields to a case-insensitive
  collation. Requires additional extensions on non-MySQL systems (e.g.
  Postgres needs `citext`). Replaces `FieldType::FIELD_TITLE` with
  `FieldType::TYPE_CHAR_NOCASE`. Field definitions: MySQL
  (`VARCHAR(255) CHARSET utf8 COLLATE utf8_general_ci`), Postgres
  (`citext NOT NULL`), SQLite (`VARCHAR(255) NOT NULL COLLATE NOCASE`,
  may need a special solution). No performance analysis has been
  performed.
- `'char-long'`, extends DIBlob and DIUri field width to 300 characters
  (from 72) for LIKE/NLIKE matching on a larger text body without a
  fulltext index. 300 was chosen to fit within MySQL/MariaDB's InnoDB
  prefix limit of 767 bytes. A larger index may carry a performance
  penalty. Requires running `rebuildData.php` after enabling.

Combine flags by listing them: `[ 'char-nocase', 'char-long' ]`.

**Since:** 3.0
**Default:** `false`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `false` (sentinel: disabled) | `SMW_FIELDT_NONE` (when assigned alone) |
| `'char-nocase'` | `SMW_FIELDT_CHAR_NOCASE` |
| `'char-long'` | `SMW_FIELDT_CHAR_LONG` |

## $smwgFixedProperties

Properties managed in their own fixed database table for sharding large
value sets. The type definition is taken from the property page
`[[Has type::...]]`; if no type is defined, `$smwgPDefaultType` is used.

Any change to a property's type requires running `setupStore.php` or the
Special:SMWAdmin table update.

```php
$smwgFixedProperties = [
    'Age',
    'Has population',
];
```

**Since:** 1.9
**Default:** `[]`

## $smwgFulltextDeferredUpdate

When `true`, defers fulltext index updates to a background process
decoupled from the storage update. Disabling this runs the index update
synchronously with the wiki page update, which may increase update lag.

**Since:** 2.5
**Default:** `true`

## $smwgFulltextLanguageDetection

Language-detector configurations for fulltext indexing; empty disables
language detection. A large language list in `TextCatLanguageDetector` has
a detrimental effect on performance when detecting language from free text.
Stopwords are only applied after language detection has been enabled.

```php
$smwgFulltextLanguageDetection = [
    'TextCatLanguageDetector' => [ 'en', 'de', 'fr', 'es', 'ja', 'zh' ],
];
```

**Since:** 2.5
**Default:** `[]`

## $smwgFulltextSearchIndexableDataTypes

Array of DataItem types indexed in the fulltext search table.

- `'blob'`, indexes property values of type Blob (Text).
- `'uri'`, indexes property values of type URI.
- `'wikipage'`, indexes property values of type Page. Not enabled by
  default because no performance analysis is available for wikis with
  a large pool of pages (10K+) or extensive page-type value
  assignments. Enabling it supports the same case-insensitivity and
  phrase-matching features as Text or URI values when using `~/!~`.

**Since:** 2.5
**Default:** `[ 'blob', 'uri' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'blob'` | `SMW_FT_BLOB` |
| `'uri'` | `SMW_FT_URI` |
| `'wikipage'` | `SMW_FT_WIKIPAGE` |

## $smwgFulltextSearchMinTokenSize

Minimum token length used to decide between MATCH and LIKE operators in
fulltext conditions. For MySQL, this should correspond to either
`innodb_ft_min_token_size` or `ft_min_word_len`.

**Since:** 2.5
**Default:** `3`

## $smwgFulltextSearchPropertyExemptionList

Property keys excluded from fulltext indexing; LIKE/NLIKE is used for
these instead. Properties are exempted because they are either
insignificant, represent single terms, or have characteristics that make
fulltext indexing unsuitable.

**Since:** 2.5
**Default:** `["_ASKFO", "_ASKST", "_ASKPA", "_IMPO", "_LCODE", "_UNIT", "_CONV", "_TYPE", "_ERRT", "_INST", "_ASK", "_SOBJ", "_PVAL", "_PVALI", "_REDI", "_CHGPRO"]`

## $smwgFulltextSearchTableOptions

Table creation options for the fulltext search index table. This setting directly influences how the fulltext table is created; change with caution. Each key is the database driver name; the value is an array of table option strings passed verbatim when the table is built.

Engine-specific notes:

- **MySQL / MariaDB** — MySQL 5.5+ supports fulltext search on MyISAM and InnoDB storage engines. MariaDB supports fulltext on MyISAM and Aria tables; InnoDB support was added in MariaDB 10.0.5, Mroonga in 10.0.15. The default `ENGINE=MyISAM, DEFAULT CHARSET=utf8` is broadly compatible. For MySQL 5.7+ the option array can include a parser directive, e.g. `[ 'ENGINE=MyISAM, DEFAULT CHARSET=utf8', 'WITH PARSER ngram' ]`.
- **SQLite** — FTS3 has been available since SQLite 3.5; FTS4 since 3.7.4; FTS5 since 3.9.0. Extra arguments can follow the module name, e.g. `[ 'FTS4', 'tokenize=porter' ]`.

**Since:** 2.5
**Default:**

```php
[
    'mysql'  => [ 'ENGINE=MyISAM, DEFAULT CHARSET=utf8' ],
    'sqlite' => [ 'FTS4' ],
]
```

## $smwgIgnoreExtensionRegistrationCheck

When `true`, suppresses the check that verifies the extension was correctly
registered via `wfLoadExtension`. Also disables the SMW 3.2 validation that
detects `wfLoadExtension('SemanticMediaWiki')` being used alongside the
deprecated `enableSemantics`.

**Since:** 3.1
**Default:** `false`

## $smwgIgnoreQueryErrors

When `true`, queries execute even if errors were detected during parsing.
A hint displaying the detected errors is shown regardless.

**Since:** 1.5
**Default:** `true`

## $smwgIgnoreUpgradeKeyCheck

When `true`, bypasses the `SetupCheck` and `MaintenanceCheck` gates that
block execution when the schema is in an intermediate state.

- The early `SetupCheck` invoked from `Setup::init` is skipped, so the
  extension loads even if the upgrade key does not match the schema.
- `MaintenanceCheck` no longer aborts maintenance scripts with the "setup
  wasn't finalized" message, allowing recovery scripts such as
  `populateHashField.php` to run when an upgrade is stalled.

**Since:** 4.1.3
**Default:** `false`

## $smwgImportFileDirs

Directories from which SMW reads import-content definitions during setup.
For all files in these directories, import is initiated when
`$smwgImportReqVersion` matches the version declared in the file.

The manifest declares `array_plus`, so users register additional
vocabularies without dropping the built-in `'smw'` entry:

```php
$GLOBALS['smwgImportFileDirs']['custom-vocab'] = __DIR__ . '/custom';
```

**Since:** 2.5
**Default:**

```php
[ 'smw' => '<smwgIP>/data/import' ]
```

**Related:** [`src/Importer/README.md`](../src/Importer/README.md) — full import-format docs.

## $smwgImportPerformers

Users reserved exclusively for the import task; they may lock content from
alteration by others. The protection is only active when specific import
content has defined `import_performer` with a listed user.

**Since:** 3.2
**Default:** `["SemanticMediaWikiImporter"]`
**Related:** See [`src/Importer/README.md`](../src/Importer/README.md).

## $smwgImportReqVersion

Import-file version required for content to be imported during setup; set
to `false` to disable import. For all files in `smwgImportFileDirs`, import
is initiated only if `smwgImportReqVersion` matches the version declared in
the file.

**Since:** 2.5
**Default:** `1`

## $smwgIP

Path to the SMW extension root directory as seen on the local filesystem.
Used to resolve PHP file paths within the extension.

**Since:** 1.0
**Default:** the extension's root directory

## $smwgJobQueueWatchlist

Job types shown in the personal-bar job-queue watchlist; empty disables the
feature. The information is fetched from the MediaWiki API and may be
slightly inaccurate. Users must also enable the watchlist in their personal
preferences.

```php
$smwgJobQueueWatchlist = [
    'smw.update',
    'smw.parserCachePurge',
    'smw.fulltextSearchTableUpdate',
    'smw.changePropagationUpdate',
];
```

**Since:** 3.0
**Default:** `[]`

## $smwgLocalConnectionConf

Connection characteristics for each named database handle used by SMW. The outer key is the connection name; the inner map specifies `read` and `write` DB index constants using MediaWiki's standard `DB_REPLICA` / `DB_PRIMARY` constants. Merged with `array_plus_2d` semantics: user-defined inner keys win; connections not defined by the user are filled from defaults.

**Changes to this setting should only be made by trained professionals** to avoid unexpected or unanticipated results when using connection handlers.

Named handles:

- `mw.db` — the general-purpose SMW database connection used for most reads and writes.
- `mw.db.queryengine` — the connection used exclusively by the query engine; separating it allows routing heavy read queries to a dedicated replica.

**Since:** 2.5.3
**Default:**

```php
[
    'mw.db' => [
        'read'  => DB_REPLICA,
        'write' => DB_PRIMARY,
    ],
    'mw.db.queryengine' => [
        'read'  => DB_REPLICA,
        'write' => DB_PRIMARY,
    ],
]
```

## $smwgMainCacheType

Main persistent cache type used by SMW. `CACHE_ANYTHING` defers to
`$wgMessageCacheType` / `$wgParserCacheType` if they are set, providing
persistence via whatever backend MediaWiki is already using.

**Since:** 3.0
**Default:** `CACHE_ANYTHING`

## $smwgMaintenanceDir

Directory containing SMW maintenance scripts.

**Since:** 2.5
**Default:** `<smwgIP>/maintenance`

## $smwgMandatorySubpropertyParentTypeInheritance

When `true`, enforces type inheritance between a parent property and its
subproperties.

**Since:** 3.1
**Default:** `false`

## $smwgMaxNonExpNumber

Largest number displayed without scientific notation. The default is large
since some users have difficulty understanding exponents. Scientific
applications may prefer a smaller value for concise display.

**Since:** 1.4.3
**Default:** `1000000000000000`

## $smwgMaxNumRecurringEvents

Maximum number of recurring-event instances that can be defined regardless
of end date.

**Since:** 1.4.3
**Default:** `500`

## $smwgMaxPropertyValues

Maximum number of property values displayed per property on a page's
Property page. For large value sets, consider reducing `$smwgPagingLimit`
for better performance.

**Since:** 1.3
**Default:** `3`

## $smwgNamespace

URI/IRI namespace for OWL/RDF export resources; auto-derived from the wiki
URL when `null`. To produce clean URIs, set this explicitly and configure
your web server accordingly.

```php
$smwgNamespace = "http://example.org/id/";
```

**Since:** 0.7
**Default:** `null`

## $smwgNamespacesWithSemanticLinks

Controls which namespaces are evaluated for semantic annotations. Pages outside listed namespaces may carry annotations but they are silently ignored and excluded from RDF export unless referenced from another article.

The manifest declares `array_plus` merge semantics, so a `LocalSettings.php` write that adds a single entry merges cleanly with the built-in defaults rather than replacing the entire map:

```php
// Enable semantics in a custom namespace without losing standard defaults
$smwgNamespacesWithSemanticLinks[NS_AUTHORITY] = true;
```

To disable a namespace that is enabled by default, set its value to `false` explicitly:

```php
$smwgNamespacesWithSemanticLinks[NS_HELP] = false;
```

**Since:** 0.7
**Default:**

```php
[
    NS_MAIN             => true,
    NS_TALK             => false,
    NS_USER             => true,
    NS_USER_TALK        => false,
    NS_PROJECT          => true,
    NS_PROJECT_TALK     => false,
    NS_FILE             => true,
    NS_FILE_TALK        => false,
    NS_MEDIAWIKI        => false,
    NS_MEDIAWIKI_TALK   => false,
    NS_TEMPLATE         => false,
    NS_TEMPLATE_TALK    => false,
    NS_HELP             => true,
    NS_HELP_TALK        => false,
    NS_CATEGORY         => true,
    NS_CATEGORY_TALK    => false,
]
```

## $smwgPageSpecialProperties

Special properties automatically tracked and stored for pages.

- `_MDAT` — Modification date (enabled by default for backward
  compatibility).
- `_TRANS` — Add annotations (language, source, etc.) when a page is
  identified as a translation page (requires the Translation extension).
- `_ATTCH_LINK` — Track embedded files and images.
- `_CDAT` — Creation date (not enabled by default).

**Note:** Use `array_push` or array-merge to extend, not `+=`:

```php
// Correct:
$smwgPageSpecialProperties[] = '_CDAT';
// Also correct:
$smwgPageSpecialProperties = array_merge( $smwgPageSpecialProperties, [ '_CDAT' ] );
// WRONG — does not work:
$smwgPageSpecialProperties += [ '_CDAT' ];
```

**Since:** 1.7
**Default:** `["_MDAT"]`

## $smwgPagingLimit

Number of results shown in the listings on pages in the Property and Concept namespaces as well as other services that require a limit. Setting a value to `0` hides the corresponding listing entirely.

- `type` — row limit for Special:Types (replaces the former `$smwgTypePagingLimit`).
- `concept` — row limit for concept-page listings (replaces `$smwgConceptPagingLimit`).
- `property` — row limit for property-page listings (replaces `$smwgPropertyPagingLimit`).
- `errorlist` — row limit for Special:ProcessingErrorList.
- `browse` — sub-map for Special:Browse value lists:
  - `valuelist.outgoing` — outgoing value list count.
  - `valuelist.incoming` — incoming value list count.

**Since:** 3.0
**Default:**

```php
[
    'type'      => 50,
    'concept'   => 250,
    'property'  => 20,
    'errorlist' => 20,
    'browse'    => [
        'valuelist.outgoing' => 30,
        'valuelist.incoming' => 20,
    ],
]
```

## $smwgParserFeatures

Array of annotation-parsing features.

- `'strict'`, strict mode: treats `[[property::value:part::also]]` as a
  single triple. Without strict mode, `[[p1::p2::value]]` assigns
  multiple properties, but may cause unexpected interpretations when
  values contain extra colons.
- `'unstrip'`, supports decoding (unstripping) of hidden text elements
  such as `<nowiki>` within an annotation value (can only be stored
  with a `_txt` type property).
- `'inline-errors'`, displays warnings inline in wikitext right after
  the problematic annotation input (replaces `smwgInlineErrors`; does
  not affect inline-query warnings).
- `'hidden-categories'`, omits hidden categories (marked with
  `__HIDDENCAT__`) from the annotation process (replaces
  `smwgShowHiddenCategories` from 1.9). Changing this requires a full
  rebuild.
- `'links-in-values'`, supports "links in values", e.g.
  `[[SomeProperty::Foo [[link]] in [[Bar::AnotherValue]]]]` (replaces
  `smwgLinksInValues` with `SMW_LINV_OBFU`; `SMW_LINV_PCRE` is no
  longer available).

**Since:** 3.0
**Default:** `[ 'strict', 'inline-errors', 'hidden-categories' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'strict'` | `SMW_PARSER_STRICT` |
| `'unstrip'` | `SMW_PARSER_UNSTRIP` |
| `'inline-errors'` | `SMW_PARSER_INL_ERROR` |
| `'hidden-categories'` | `SMW_PARSER_HID_CATS` |
| `'links-in-values'` | `SMW_PARSER_LINKS_IN_VALUES` (alias: `SMW_PARSER_LINV`) |

## $smwgPDefaultType

Internal type ID assumed for properties that have no explicit type
declaration; defaults to `Type:Page`. See the language files in
`languages/SMW_LanguageXX.php` for type IDs in other languages.

**Since:** 1.1.2
**Default:** `"_wpg"`

## $smwgPlainList

When `true`, `format=list` produces plain lists without HTML markup,
restoring pre-3.0 behaviour. When `false`, use `format=plainlist` to get
plain lists.

**Since:** 3.1.2
**Default:** `false`

## $smwgPostEditUpdate

Regulates task-specific settings for the post-edit process. The main objective is to defer secondary updates until after the GET request has been finalised so that resource requirements are part of an API request rather than a GET request, keeping the client responsive independent of update workload.

Sub-keys:

- `run-jobs` — jobs to execute on post-edit to run in a timely manner independent of the user's job-scheduler environment. The integer value is the expected number of jobs to execute per request.
- `purge-page` — controls active page-purge behaviour:
  - `on-outdated-query-dependency` — when `true`, issues an API-driven page purge after a post-edit so that newly stored annotation values (including those depending on query output) are recomputed, not just the parser cache.
- `check-query` — **(experimental)** uses the `post-edit` event to re-run embedded queries and compare their `result_hash` before and after; if the hash differs the page is reloaded to show fresh results. Because this invokes the API as a background task for every page that embeds a query, it is strongly recommended to enable this only together with the query cache (`$smwgQueryResultCacheType`) and the query dependency links store (`$smwgEnabledQueryDependencyLinksStore`).

**Since:** 3.0
**Default:**

```php
[
    'check-query' => false,
    'run-jobs'    => [
        'smw.fulltextSearchTableUpdate' => 1,
    ],
    'purge-page'  => [
        'on-outdated-query-dependency' => true,
    ],
]
```

## $smwgPropertyInvalidCharacterList

Characters considered invalid in property labels; annotations using them
produce an error.

**Since:** 2.5
**Default:** `["[", "]", "|", "<", ">", "{", "}", "+", "–", "%", "\r", "\n", "?", "*", "!"]`

## $smwgPropertyListLimit

Row limits for the sub-lists shown on a property page. Setting a value to `false` disables the display of that list entirely.

- `subproperty` — maximum number of subproperties to show.
- `redirect` — maximum number of redirect entries to show.
- `error` — maximum number of improper-assignment error entries to show.

**Since:** 3.0
**Default:**

```php
[
    'subproperty' => 25,
    'redirect'    => 25,
    'error'       => 10,
]
```

## $smwgPropertyLowUsageThreshold

Usage count below which a property is highlighted as "hardly used" on
Special:Properties.

**Since:** 1.9
**Default:** `5`

## $smwgPropertyReservedNameList

Names reserved as property names because they interfere with SMW or
MediaWiki internals. Removing default names is not recommended; the list
can be extended for wiki-specific use cases. Entries can be simple names or
identifiers starting with `smw-property-reserved-` to link to a
translatable representation in the content language.

**Since:** 3.0
**Default:** `["Category", "smw-property-reserved-category"]`

## $smwgPropertyRetiredList

Property prefixes / IDs marked as retired and eligible for removal from the
entity table. Listed properties are removed when the system encounters them,
avoiding references to or display of properties that are no longer in use.

**Since:** 3.1
**Default:** `["_SF_", "_SD_"]`

## $smwgPropertyZeroCountDisplay

When `true`, properties with zero usage are shown on Special:Properties.

**Since:** 1.9
**Default:** `true`

## $smwgQComparators

Pipe-delimited list of comparator operators supported in queries.

- `<` — smaller than (or equal to, when `$smwgQStrictComparators` is
  `false`).
- `>` — greater than (or equal to, when `$smwgQStrictComparators` is
  `false`).
- `!` — unequal to.
- `~` — pattern match with `*` as wildcard.
- `!~` — negated pattern match (for `Type:String`; must appear before `!`
  and `~` in the list to be matched correctly).
- `≤` — smaller than or equal to.
- `≥` — greater than or equal to.
- `like:` / `nlike:` — when a fulltext index is enabled, express
  LIKE / NLIKE using the primary fulltext match operation.

Unsupported comparators are treated as part of the queried value.

**Since:** 1.0
**Default:** `"<|>|!~|!|~|≤|≥|<<|>>|~=|like:|nlike:|in:|not:|phrase:"`

## $smwgQConceptCacheLifetime

Concept cache lifetime in minutes; SMW recomputes an older cache if this
threshold is exceeded (and if settings permit). Cached results are always
used when available regardless of this threshold.

**Since:** 2.1
**Default:** `1440`

## $smwgQConceptCaching

Controls when concepts require a pre-computed cache. Concept queries
that would not be allowed as normal inline queries will not be executed
directly but can use pre-computed results instead.

- `'all'`, shows concept elements only if they are cached.
- `'hard'`, shows without cache if the concept is no harder than a
  permitted inline query; otherwise requires cache.
- `'none'`, shows all concepts even without any cache.

Cached results are always used when available, regardless of this
setting.

**Since:** 1.0
**Default:** `'hard'`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'all'` | `CONCEPT_CACHE_ALL` |
| `'hard'` | `CONCEPT_CACHE_HARD` |
| `'none'` | `CONCEPT_CACHE_NONE` |

## $smwgQConceptFeatures

Array of query types available inside concept definitions.

- `'property'`, property-based conditions.
- `'category'`, category-based conditions.
- `'concept'`, nested concept conditions.
- `'namespace'`, namespace-based conditions.
- `'conjunction'`, conjunction (`AND`) conditions.
- `'disjunction'`, disjunction (`OR`) conditions.

**Since:** 1.0
**Default:** `[ 'property', 'category', 'concept', 'namespace', 'conjunction', 'disjunction' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'property'` | `SMW_PROPERTY_QUERY` |
| `'category'` | `SMW_CATEGORY_QUERY` |
| `'concept'` | `SMW_CONCEPT_QUERY` |
| `'namespace'` | `SMW_NAMESPACE_QUERY` |
| `'conjunction'` | `SMW_CONJUNCTION_QUERY` |
| `'disjunction'` | `SMW_DISJUNCTION_QUERY` |

## $smwgQConceptMaxDepth

Maximum property-chain depth for a concept query (same role as
`$smwgQMaxDepth` but scoped to concept pages).

**Since:** 2.1
**Default:** `8`

## $smwgQConceptMaxSize

Maximum number of conditions permitted in a concept query (same role as
`$smwgQMaxSize` but scoped to concept pages).

**Since:** 2.1
**Default:** `20`

## $smwgQDefaultLimit

Default number of result rows returned by an inline query. Can be overridden
per-query with `limit=num` in `#ask`.

**Since:** 1.0
**Default:** `50`

## $smwgQDefaultLinking

Default linking behaviour for query results; one of `none`, `subject`
(first column only), or `all`.

**Since:** 1.0
**Default:** `"all"`

## $smwgQDefaultNamespaces

Default namespaces searched by queries; `null` disables namespace
restrictions for faster queries. Example with explicit namespace list:

```php
$smwgQDefaultNamespaces = [ NS_MAIN, NS_FILE ];
```

**Since:** 1.0
**Default:** `null`

## $smwgQEnabled

Master switch to enable or disable all query-related features and
interfaces.

**Since:** 1.0
**Default:** `true`

## $smwgQEqualitySupport

Depth of redirect equality evaluation in queries.

- `'none'`, never evaluates redirects as equality between page names.
- `'some'`, evaluates redirects as equality, with possible
  performance-relevant restrictions depending on the storage engine.
- `'full'`, evaluates redirects as equality in all cases.

**Since:** 1.0
**Default:** `'some'`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'none'` | `SMW_EQ_NONE` |
| `'some'` | `SMW_EQ_SOME` |
| `'full'` | `SMW_EQ_FULL` |

## $smwgQExpensiveExecutionLimit

Maximum number of expensive `#ask` / `#show` calls per page; `false` means
no limit. A call is classified as expensive when its execution time exceeds
`$smwgQExpensiveThreshold`.

**Since:** 3.0
**Default:** `false`

## $smwgQExpensiveThreshold

Time in seconds above which a `#ask` / `#show` call is classified as
expensive and counted towards `$smwgQExpensiveExecutionLimit`.

**Since:** 3.0
**Default:** `10`

## $smwgQFeatures

Array of query types available by default.

- `'property'`, property-based conditions.
- `'category'`, category-based conditions.
- `'concept'`, concept-based conditions.
- `'namespace'`, namespace-based conditions.
- `'conjunction'`, conjunction (`AND`) conditions.
- `'disjunction'`, disjunction (`OR`) conditions.

Examples:

```php
// Only category intersections:
$smwgQFeatures = [ 'category', 'conjunction' ];

// Only single concepts:
$smwgQFeatures = [ 'concept' ];

// Everything except disjunctions:
$smwgQFeatures = [ 'property', 'category', 'concept', 'namespace', 'conjunction' ];
```

**Since:** 1.2
**Default:** `[ 'property', 'category', 'concept', 'namespace', 'conjunction', 'disjunction' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'property'` | `SMW_PROPERTY_QUERY` |
| `'category'` | `SMW_CATEGORY_QUERY` |
| `'concept'` | `SMW_CONCEPT_QUERY` |
| `'namespace'` | `SMW_NAMESPACE_QUERY` |
| `'conjunction'` | `SMW_CONJUNCTION_QUERY` |
| `'disjunction'` | `SMW_DISJUNCTION_QUERY` |

The `SMW_ANY_QUERY` "all flags" constant is similarly retired: list every
permitted query type explicitly in the array form.

## $smwgQFilterDuplicates

When `true`, filters duplicate query segments from the query build process
(experimental). Duplicate segments represent the same query signature and
filtering them eliminates redundant computational effort.

**Since:** 2.5
**Default:** `false`

## $smwgQMaxDepth

Maximum property-chain depth of a query (e.g.
`[[rel::<q>[[rel2::Test]]</q>]]` has depth 2).

**Since:** 1.0
**Default:** `4`

## $smwgQMaxInlineLimit

Maximum number of result rows printed by a single inline query on a page.

**Since:** 1.0
**Default:** `500`

## $smwgQMaxLimit

Maximum number of results ever retrieved, even on special query pages.

**Since:** 1.0
**Default:** `10000`

## $smwgQMaxSize

Maximum number of conditions permitted in a single query. Use
`format=debug` in a query to inspect its condition count.

**Since:** 1.0
**Default:** `16`

## $smwgQPrintoutLimit

Maximum number of printout columns (`?`-statements) supported in a single
query.

**Since:** 1.0
**Default:** `100`

## $smwgQSortFeatures

Array of query sort capabilities.

- `'sort'`, general sort support for query results (replaces
  `smwgQSortingSupport`).
- `'random'`, random sorting support (replaces `smwgQRandSortingSupport`).
- `'unconditional'`, allows unconditional sort of results even if the
  sort property is not part of the result set. Not implemented for
  SPARQLStore; ElasticStore requires `sort.property.must.exists` to
  be disabled for equivalent sorting behaviour.

**Since:** 3.0
**Default:** `[ 'sort', 'random' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'sort'` | `SMW_QSORT` |
| `'random'` | `SMW_QSORT_RANDOM` |
| `'unconditional'` | `SMW_QSORT_UNCONDITIONAL` |

## $smwgQStrictComparators

When `true`, `<` and `>` comparators are strict (equal values are not
accepted). When `false`, they behave as `≤` and `≥`.

**Since:** 1.5.3
**Default:** `false`

## $smwgQSubcategoryDepth

Maximum depth for sub-category inclusion steps within the category
hierarchy. Use `0` to disable hierarchy inferencing in queries.

**Since:** 1.0
**Default:** `10`

## $smwgQSubpropertyDepth

Maximum depth for sub-property inclusion steps within the property
hierarchy. Use `0` to disable hierarchy inferencing in queries.

**Since:** 1.0
**Default:** `10`

## $smwgQTemporaryTablesAutoCommitMode

When `true`, forces auto-commit for temporary tables to work around MySQL
GTID restrictions. MySQL's Global Transaction Identifier requires that
`CREATE TEMPORARY TABLE` not be used inside transactions when
`@@GLOBAL.ENFORCE_GTID_CONSISTENCY = 1`. Enabling this setting forces an
auto-commit for such temporary table operations.

**Since:** 2.5
**Default:** `false`

## $smwgQueryDependencyPropertyExemptionList

Property keys excluded from query-dependency detection to avoid spurious
cache purges.

- `_MDAT` excluded to avoid a purge on every page edit that changes
  `Modification date`.
- `_SOBJ` excluded to avoid triggering purges for each altered subobject
  (subobject-defined properties are still tracked unless also listed).
- `_ASKDU` excluded because changes to query duration have no bearing on
  query result lists.

**Since:** 2.3
**Default:** `["_MDAT", "_SOBJ", "_ASKDU", "_ASKDE", "_ASKSI", "_ASKFO", "_ASKST"]`

## $smwgQueryProfiler

Query profiler controls. Accepts:

- `false`, disables profiling entirely. Disabling may impact secondary
  processes that rely on profile information (e.g. the notification
  system).
- `[]`, enables basic profiling with no detail fields. Note that this
  does NOT disable; use `false` to disable.
- An array of any of the detail-field strings below, to enable
  profiling with those extra fields recorded.

Detail-field strings:

- `'duration'`, records query duration (time between result selection
  and output).
- `'parameters'`, records query parameters needed to regenerate a
  query result via a background job.

**Note:** If this setting is changed, run `update.php` / `rebuildData.php`.

```php
$smwgQueryProfiler = [ 'duration', 'parameters' ];
```

**Since:** 1.9
**Default:** `[]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| Value | Legacy form |
|---|---|
| `[]` | `true` (was the previous default) |
| `'duration'` | `SMW_QPRFL_DUR` |
| `'parameters'` | `SMW_QPRFL_PARAMS` |

## $smwgQueryResultCacheLifetime

Lifetime in seconds for embedded query result caches; default one week.
Requires `$smwgQueryResultCacheType` to be set to a non-`CACHE_NONE` value.

**Since:** 2.5
**Default:** `604800`

## $smwgQueryResultCacheRefreshOnPurge

When `true`, embedded query result caches are invalidated when an
`action=purge` event fires.

**Since:** 2.5
**Default:** `true`

## $smwgQueryResultCacheType

Cache backend for query result storage; `CACHE_NONE` disables the query
result cache. Stores computed subject lists from the QueryEngine (not the
rendered string from a result printer). When enabled, queries with the same
signature (fingerprint) share a cache entry, reducing fragmentation.

Recommended to also enable `$smwgEnabledQueryDependencyLinksStore` for
automatic cache eviction when queried entities change.

**Since:** 2.5
**Default:** `CACHE_NONE`

## $smwgQueryResultNonEmbeddedCacheLifetime

Lifetime in seconds for non-embedded (Special:Ask, API) query result caches
when `$smwgQueryResultCacheType` is enabled. Setting to `0` or `false`
disables caching for non-embedded queries.

Non-embedded queries cannot be tracked by the `QueryDependencyLinksStore`
(no subject entity to identify them), so automatic purge is not available.
Choose the lifetime carefully. This setting can also reduce DoS risk by
preventing unlimited query requests from Special:Ask or the API from locking
the database.

**Note:** Non-embedded queries cannot be auto-purged via query dependency
tracking because the subject entity is missing. The lifetime must therefore
be chosen with care.

**Since:** 2.5
**Default:** `600`

## $smwgQuerySources

Available external query sources; unknown sources fall back to the local
wiki. A query class handler must implement the `QueryEngine` interface; if
it needs store access, implement `StoreAware` as well.

```php
$smwgQuerySources = [
    'mw-wiki-foo' => [
        '\SMW\Query\RemoteRequest',
        'url' => 'http://example.org/wiki/index.php',
    ],
];
```

**Since:** 1.4.3
**Default:** `[]`

## $smwgQUpperbound

Maximum rows printable in an inline query when an offset is applied.

**Since:** 2.1
**Default:** `5000`

## $smwgQUseLegacyQuery

When `true`, reverts to the pre-7.x `SELECT DISTINCT` query shape instead
of the derived-table rewrite. The default derived-table rewrite avoids
inefficient query plans that MariaDB tends to pick when `DISTINCT` and
`ORDER BY` are combined. Toggle to `true` if you encounter query performance
regressions after upgrading and want to fall back while reporting the issue.

**Since:** 7.0.0
**Default:** `false`

## $smwgRemoteReqFeatures

Array of remote-request handling features for Special:Ask.

- `'send-response'`, allows Special:Ask to respond to remote requests
  in combination with `$smwgQuerySources` and the `RemoteRequest`
  handler.
- `'show-note'`, shows a note for each remote request so users are
  aware that results were retrieved from an external source.

If `$smwgQuerySources` contains no entries, remote requests are not
supported regardless of this setting.

**Since:** 3.0
**Default:** `[ 'send-response', 'show-note' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'send-response'` | `SMW_REMOTE_REQ_SEND_RESPONSE` |
| `'show-note'` | `SMW_REMOTE_REQ_SHOW_NOTE` |

## $smwgResultAliases

Predefined aliases mapping alternative format names to canonical result formats. The manifest declares `array_plus`, so extensions and `LocalSettings.php` additions merge with the built-in set rather than replacing it.

To disable an alias, `unset` it after the extension is loaded:

```php
unset( $smwgResultAliases['rss'] );
```

Disabled aliases are treated as if the alias parameter had been omitted.

**Since:** 1.8
**Default:**

```php
[
    'feed'         => [ 'rss' ],
    'templatefile' => [ 'template file' ],
    'plainlist'    => [ 'plain' ],
]
```

## $smwgResultFormats

Map of available result formats for `#ask` and `#show` queries; each key is the format name and each value is the fully-qualified result-printer class. The manifest declares `array_plus`, so extensions register their own formats by adding entries and `LocalSettings.php` additions merge with the built-in set.

The formats `table` and `list` are built-in defaults that cannot be disabled. The `broadtable` format should also not be disabled as it is used by Special:Ask.

To disable a format after the extension is loaded:

```php
unset( $smwgResultFormats['template'] );
```

Disabled formats are treated as if the `format` parameter had been omitted.

**Since:** 1.0
**Default:**

```php
[
    'table'        => 'SMW\Query\ResultPrinters\TableResultPrinter',
    'broadtable'   => 'SMW\Query\ResultPrinters\TableResultPrinter',
    'list'         => 'SMW\Query\ResultPrinters\ListResultPrinter',
    'plainlist'    => 'SMW\Query\ResultPrinters\ListResultPrinter',
    'ol'           => 'SMW\Query\ResultPrinters\ListResultPrinter',
    'ul'           => 'SMW\Query\ResultPrinters\ListResultPrinter',
    'category'     => 'SMW\Query\ResultPrinters\CategoryResultPrinter',
    'embedded'     => 'SMW\Query\ResultPrinters\EmbeddedResultPrinter',
    'template'     => 'SMW\Query\ResultPrinters\ListResultPrinter',
    'count'        => 'SMW\Query\ResultPrinters\NullResultPrinter',
    'debug'        => 'SMW\Query\ResultPrinters\NullResultPrinter',
    'feed'         => 'SMW\Query\ResultPrinters\FeedExportPrinter',
    'csv'          => 'SMW\Query\ResultPrinters\CsvFileExportPrinter',
    'templatefile' => 'SMW\Query\ResultPrinters\TemplateFileExportPrinter',
    'dsv'          => 'SMW\Query\ResultPrinters\DsvResultPrinter',
    'json'         => 'SMW\Query\ResultPrinters\JsonResultPrinter',
    'rdf'          => 'SMW\Query\ResultPrinters\RdfResultPrinter',
]
```

**Related:** [Help:Result formats](https://www.semantic-mediawiki.org/wiki/Help:Result_formats)

## $smwgResultFormatsFeatures

Result-printer feature. Accepts one of:

- `'none'`, no additional features.
- `'template-outsep'`, uses the `sep` parameter as the outer separator
  in template-based result printers.

**Since:** 2.3
**Default:** `'template-outsep'`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'none'` | `SMW_RF_NONE` |
| `'template-outsep'` | `SMW_RF_TEMPLATE_OUTSEP` |

## $smwgSearchByPropertyFuzzy

Type IDs for which Special:SearchByProperty displays nearby fuzzy results
when exact matches are few. Switch off if this page has performance problems.

**Since:** 2.1
**Default:** `["_num", "_txt", "_dat", "_mlt_rec"]`

## $smwgServicesFileDir

Directory containing SMW service-wiring files loaded by the dependency
injection container.

**Since:** 2.5
**Default:** `<smwgIP>/src/Services`

## $smwgSetParserCacheKeys

Controls whether the parser cache is fragmented by the viewer's interface
language. The only recognized key is `userlang` (the default), which varies the
cache for language-dependent output such as errors and localized tooltips. Set
to `[]` to disable this; on a multilingual wiki that can serve such output in
the wrong language.

**Since:** 5.1
**Default:** `["userlang"]`

## $smwgSetParserCacheTimestamp

When `true`, sets a timestamp on `ParserOutput` to allow immediate
parser-cache invalidation.

**Note:** Enabling this means the "last modified" date shown to browsers
becomes the parser-cache purge time rather than the page edit time, and CDN
caches may not be invalidated correctly because the revision is not old
enough to be considered stale.

**Since:** 5.1
**Default:** `true`

## $smwgShowFactbox

Controls factbox visibility on page views. Accepts one of:

- `'nonempty'`, shows only factboxes that have some content.
- `'special'`, shows only if special properties were set.
- `'hidden'`, always hides.
- `'shown'`, always shows.

**Note:** The magic words `__SHOWFACTBOX__` and `__HIDEFACTBOX__` can be
used to control factbox display for individual pages, overriding this
setting.

**Since:** 0.7
**Default:** `'hidden'`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'hidden'` | `SMW_FACTBOX_HIDDEN` |
| `'special'` | `SMW_FACTBOX_SPECIAL` |
| `'nonempty'` | `SMW_FACTBOX_NONEMPTY` |
| `'shown'` | `SMW_FACTBOX_SHOWN` |

## $smwgShowFactboxEdit

Controls factbox visibility in edit mode; accepts the same values as
`$smwgShowFactbox` (`'hidden'`, `'special'`, `'nonempty'`, `'shown'`).

**Since:** 1.0
**Default:** `'nonempty'`

### Legacy constants

Same as `$smwgShowFactbox`. Deprecated in 7.x, removed in 8.0.

## $smwgSimilarityLookupExemptionProperty

Property whose annotation exempts a property from the similarity lookup.
For example, if `Governance level` has
`[[owl:differentFrom::Governance level of]]`, the similarity lookup is
suppressed for both `Governance level` and `Governance level of` when they
are compared against each other.

**Since:** 2.5
**Default:** `"owl:differentFrom"`

## $smwgSparqlCustomConnector

Custom SPARQL repository connector class used when
`smwgSparqlRepositoryConnector` is set to `custom`. Must implement the
interface defined by `GenericRepositoryConnector`. Has no effect when
`smwgSparqlRepositoryConnector` is set to any other value.

**Since:** 2.0
**Default:** `"\\SMW\\SPARQLStore\\RepositoryConnectors\\GenericRepositoryConnector"`

## $smwgSparqlEndpoint

Service URLs for the SPARQL repository used when SPARQL-based features are enabled (e.g. when `$smwgDefaultStore` is set to `SPARQLStore`). The default `GenericRepositoryConnector` works with any database that supports SPARQL 1.1 and SPARQL Update.

Three endpoint types are configured:

- `query` — the SPARQL query endpoint for read operations such as `SELECT`. **Required.**
- `update` — the SPARQL Update endpoint for write operations. Omitting or leaving empty reduces functionality (the SPARQLStore will not be able to write data).
- `data` — the SPARQL HTTP Protocol for Graph Management endpoint. Always optional, but some repositories handle bulk graph operations more efficiently through this endpoint than through SPARQL Update.

**Since:** 1.6
**Default:**

```php
[
    'query'  => 'http://localhost:8080/sparql/',
    'update' => 'http://localhost:8080/update/',
    'data'   => 'http://localhost:8080/data/',
]
```

## $smwgSparqlDefaultGraph

Default graph URI for the SPARQL repository, analogous to a database name
in relational stores. Different wikis should use different default graphs
unless there is a good reason to share one. Leaving it empty only works if
the repository is configured to use a default graph or supports it natively.

**Since:** 1.7
**Default:** `""`

## $smwgSparqlQFeatures

Array of SPARQL query features expected to be supported by the
repository.

- `'redirects'`, supports finding redirects using inverse property
  paths; requires full SPARQL 1.1 (e.g. Fuseki, Sesame).
- `'subproperties'`, resolves subproperties.
- `'subcategories'`, resolves subcategories.
- `'collation'`, adds sorting collation support as configured in
  `$smwgEntityCollation`.
- `'no-case'`, supports case-insensitive pattern matches.

Check with your repository provider whether SPARQL 1.1 is fully
supported; if not, use `[]` (no additional features, basic SPARQL 1.1
only).

**Since:** 2.3
**Default:** `[ 'redirects', 'subproperties', 'subcategories' ]`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'redirects'` | `SMW_SPARQL_QF_REDI` |
| `'subproperties'` | `SMW_SPARQL_QF_SUBP` |
| `'subcategories'` | `SMW_SPARQL_QF_SUBC` |
| `'collation'` | `SMW_SPARQL_QF_COLLATION` |
| `'no-case'` | `SMW_SPARQL_QF_NOCASE` |

`SMW_SPARQL_QF_NONE` (the "all-off" sentinel) is replaced by `[]`.

## $smwgSparqlReplicationPropertyExemptionList

Properties exempted from the SPARQL replication process.

**Since:** 2.5
**Default:** `[]`

## $smwgSparqlRepositoryConnector

Pre-deployed SPARQL repository connector to use. When set to `custom`,
the `$smwgSparqlCustomConnector` class is used instead.

Standard connectors (`$smwgSparqlCustomConnector` has no effect when one
of these is selected): `4store`, `blazegraph`, `fuseki`, `sesame`,
`virtuoso`.

**Since:** 2.0
**Default:** `"default"`

## $smwgSparqlRepositoryFeatures

Repository-level SPARQL features such as connection-ping support.

- `'none'`, no additional repository features.
- `'connection-ping'`, verifies that a connection can be established
  before starting an update or query process, allowing for an
  uninterrupted operation.

**Since:** 3.2
**Default:** `'none'`

### Legacy constants

Deprecated in 7.x, removed in 8.0:

| String | Legacy constant |
|---|---|
| `'none'` | `SMW_SPARQL_NONE` |
| `'connection-ping'` | `SMW_SPARQL_CONNECTION_PING` |

## $smwgSpecialAskFormSubmitMethod

HTTP method used by the Special:Ask form.

- `'post'`, uses `POST`; allows jumping directly to the search result
  but produces no copyable URL (use the result bookmark button
  instead).
- `'get'`, uses `GET`; was the default until 2.5; cannot jump directly
  to the search result after a submit.
- `'get.redirect'`, uses `GET` with a redirect; can jump directly to
  the search result but requires an extra HTTP request.

**Since:** 3.0
**Default:** `'post'`

### Legacy constants

The `SMW_SASK_SUBMIT_*` constants resolve to these string values and
continue to work without a deprecation notice. The mapping is:

| String | Legacy constant |
|---|---|
| `'post'` | `SMW_SASK_SUBMIT_POST` |
| `'get'` | `SMW_SASK_SUBMIT_GET` |
| `'get.redirect'` | `SMW_SASK_SUBMIT_GET_REDIRECT` |

## $smwgSupportSectionTag

When `true`, enables `<smwsection>…</smwsection>` tag support.

**Since:** 3.0
**Default:** `true`

## $smwgTranslate

(Disabled feature) When `true`, translates browser labels using interwiki
links. This feature is not functional.

**Since:** 0.7
**Default:** `false`

## $smwgUpgradeKey

Version key that verifies a correct upgrade path was run against the DB
schema. Whenever a DB table change occurs, the key is updated (e.g.
`smw:20...`) to require clients to follow the upgrade process. Once the
installer completes, `.smw.json` is updated and no longer triggers an
exception.

**Since:** 3.0
**Default:** `"smw:2020-04-18"`

## $smwgURITypeSchemeList

URI schemes accepted by the URI datatype.

**Since:** 3.0
**Default:** `["http", "https", "mailto", "tel", "ftp", "sftp", "news", "file", "urn", "telnet", "ldap", "gopher", "ssh", "git", "irc", "ircs"]`

## $smwgUseComparableContentHash

When `true`, normalises subobject content hashes so property-value order
does not affect hash equality. For example, `|Has text=Foo,Bar|+sep=,`
and `|Has text=Bar,Foo|+sep=,` will yield the same hash. This setting was
provided for temporary backwards compatibility and is expected to become
unconditionally enabled.

**Since:** 3.0
**Default:** `true`
