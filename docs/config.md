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

## $smwgAllowRecursiveExport

When `true`, normal users can request recursive OWL/RDF exports.

**Since:** 0.7
**Default:** `false`

## $smwgAutoRefreshOnPageMove

When `true`, refreshes semantic data in the store when a page is moved.

**Since:** 1.9
**Default:** `true`

## $smwgAutoRefreshOnPurge

When `true`, refreshes semantic data in the store when a page is manually purged.

**Since:** 1.9
**Default:** `true`

## $smwgAutoRefreshSubject

When `true`, refreshes the semantic store for pages that are edited.

**Since:** 1.5.6
**Default:** `true`

## $smwgChangePropagationProtection

When `true`, protects an active change propagation from being disabled without administrative intervention.

**Since:** 3.0
**Default:** `true`

## $smwgChangePropagationWatchlist

Property IDs that trigger full re-processing of dependent pages when their values change.

**Since:** 1.5
**Default:** `["_PVAL", "_LIST", "_PVAP", "_PVUC", "_PDESC", "_PPLB", "_PREC", "_PDESC", "_SUBP", "_SUBC", "_PVALI"]`

## $smwgCheckForRemnantEntities

Controls when remnant entity checks run: `"purge"` (on purge only), `true` (every update), or `false` (disabled).

**Since:** 3.1
**Default:** `"purge"`

## $smwgCompactLinkSupport

When `true`, encodes and compresses Special:Browse / Special:Ask / Special:SearchByProperty links to reduce URL length.

**Since:** 3.0
**Default:** `false`

## $smwgCreateProtectionRight

User right required to create new properties; `false` disables creation protection.

**Since:** 3.0
**Default:** `false`

## $smwgDataTypePropertyExemptionList

DataTypes exempted from the automatic corresponding-property registration.

**Since:** 2.5
**Default:** `["Record", "Reference", "Keyword"]`

## $smwgDefaultLoggerRole

Logging granularity role (`developer`, `user`, or `production`); controls which SMW events are written to the debug log.

**Since:** 3.0
**Default:** `"production"`

## $smwgDefaultNumRecurringEvents

Default number of recurring-event instances generated when no end date is set.

**Since:** 1.4.3
**Default:** `100`

## $smwgDefaultOutputFormatters

Default output formatter overrides keyed by type ID, type name, or property name.

**Since:** 3.0
**Default:** `[]`

## $smwgDefaultStore

Default storage backend class; `SQLStore::class` evaluates to this fully-qualified string at compile time.

**Since:** 0.7
**Default:** `"SMW\\SQLStore\\SQLStore"`
**Related:** See [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md) for ElasticStore setup.

## $smwgDeprecationNotices

Registry of deprecation notices shown in the Special:Admin "Deprecation notices" panel.

**Since:** 7.0.0
**Default:** `[]`

## $smwgDetectOutdatedData

When `true`, verifies semantic data is up to date with the latest revision on every page load.

**Since:** 3.2
**Default:** `false`

## $smwgEditProtectionRight

User right required to edit pages protected via `Is edit protected`; `false` disables the feature.

**Since:** 2.5
**Default:** `false`

## $smwgElasticsearchCredentials

ElasticSearch HTTP basic-authentication credentials (`user`/`pass`).

**Since:** 4.2
**Default:** `[]`
**Related:** See [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md).

## $smwgElasticsearchEndpoints

ElasticSearch node endpoint definitions (host/port/scheme objects or `host:port` strings).

**Since:** 3.0
**Default:** `[]`
**Related:** See [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md).

## $smwgElasticsearchProfile

Path to a JSON profile that overrides `smwgElasticsearchConfig` values; `false` disables profile loading.

**Since:** 3.0
**Default:** `false`
**Related:** See [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md).

## $smwgEnabledDeferredUpdate

When `true`, defers store updates via MediaWiki's `DeferredUpdates` mechanism.

**Since:** 2.4
**Default:** `true`

## $smwgEnabledEditPageHelp

When `true`, displays SMW help information on the edit page.

**Since:** 2.1
**Default:** `false`

## $smwgEnabledFulltextSearch

When `true`, stores text in a separate fulltext-indexed table for SQL fulltext operations.

**Since:** 2.5
**Default:** `false`

## $smwgEnabledQueryDependencyLinksStore

When `true`, stores query dependencies to enable parser-cache invalidation when queried entities change.

**Since:** 2.3
**Default:** `false`

## $smwgEnabledSpecialPage

Special pages on which SMW annotation and content processing is enabled.

**Since:** 1.9
**Default:** `["Ask"]`

## $smwgEnableExportRDFLink

When `true`, adds a `Special:ExportRDF` `<link>` element to every page `<head>`.

**Since:** 5.0
**Default:** `true`

## $smwgEnableUpdateJobs

When `false`, disables MediaWiki job-queue updates triggered by semantic data changes.

**Since:** 1.1.2
**Default:** `true`

## $smwgEntityCollation

Collation strategy for entity sort values; must match `$wgCategoryCollation`; changes require running `updateEntityCollation.php`.

**Since:** 3.0
**Default:** `"identity"`

## $smwgExportBacklinks

When `true`, backlinks are included by default in OWL/RDF exports.

**Since:** 0.7
**Default:** `true`

## $smwgExportBCAuxiliaryUse

BC: when `true`, retains the legacy `aux`-marker URIs in RDF/Turtle exports.

**Since:** 2.3
**Default:** `false`

## $smwgExportBCNonCanonicalFormUse

BC: when `true`, uses localized rather than canonical identifiers in RDF/Query statements.

**Since:** 2.3
**Default:** `false`

## $smwgExportResourcesAsIri

When `true`, resources are exported as IRIs (RFC 3987) instead of ASCII-encoded URIs.

**Since:** 2.5
**Default:** `true`

## $smwgFallbackSearchType

Search engine class to fall back to when SMWSearch cannot parse a query; `null` uses the database default.

**Since:** 2.1
**Default:** `null`

## $smwgFieldTypeFeatures

SQLStore field-type modifications (case-insensitive collation / extended field width); `false` (= `SMW_FIELDT_NONE`) disables all flags.

**Since:** 3.0
**Default:** `false`

## $smwgFixedProperties

Properties managed in their own fixed database table for sharding large value sets.

**Since:** 1.9
**Default:** `[]`

## $smwgFulltextDeferredUpdate

When `true`, defers fulltext index updates to a background process decoupled from the storage update.

**Since:** 2.5
**Default:** `true`

## $smwgFulltextLanguageDetection

Language-detector configurations for fulltext indexing; empty disables language detection.

**Since:** 2.5
**Default:** `[]`

## $smwgFulltextSearchMinTokenSize

Minimum token length used to decide between MATCH and LIKE operators in fulltext conditions.

**Since:** 2.5
**Default:** `3`

## $smwgFulltextSearchPropertyExemptionList

Property keys excluded from fulltext indexing; LIKE/NLIKE is used for these instead.

**Since:** 2.5
**Default:** `["_ASKFO", "_ASKST", "_ASKPA", "_IMPO", "_LCODE", "_UNIT", "_CONV", "_TYPE", "_ERRT", "_INST", "_ASK", "_SOBJ", "_PVAL", "_PVALI", "_REDI", "_CHGPRO"]`

## $smwgIgnoreExtensionRegistrationCheck

When `true`, suppresses the check that verifies the extension was correctly registered via `wfLoadExtension`.

**Since:** 3.1
**Default:** `false`

## $smwgIgnoreQueryErrors

When `true`, queries execute even if errors were detected during parsing.

**Since:** 1.5
**Default:** `true`

## $smwgIgnoreUpgradeKeyCheck

When `true`, bypasses the `SetupCheck` and `MaintenanceCheck` gates that block execution when the schema is in an intermediate state.

**Since:** 4.1.3
**Default:** `false`

## $smwgImportPerformers

Users reserved exclusively for the import task; they may lock content from alteration by others.

**Since:** 3.2
**Default:** `["SemanticMediaWikiImporter"]`
**Related:** See [`src/Importer/README.md`](../src/Importer/README.md).

## $smwgImportReqVersion

Import-file version required for content to be imported during setup; set to `false` to disable import.

**Since:** 2.5
**Default:** `1`

## $smwgJobQueueWatchlist

Job types shown in the personal-bar job-queue watchlist; empty disables the feature.

**Since:** 3.0
**Default:** `[]`

## $smwgMandatorySubpropertyParentTypeInheritance

When `true`, enforces type inheritance between a parent property and its subproperties.

**Since:** 3.1
**Default:** `false`

## $smwgMaxNonExpNumber

Largest number displayed without scientific notation.

**Since:** 1.4.3
**Default:** `1000000000000000`

## $smwgMaxNumRecurringEvents

Maximum number of recurring-event instances that can be defined regardless of end date.

**Since:** 1.4.3
**Default:** `500`

## $smwgMaxPropertyValues

Maximum number of property values displayed per property on a page's Property page.

**Since:** 1.3
**Default:** `3`

## $smwgNamespace

URI/IRI namespace for OWL/RDF export resources; auto-derived from the wiki URL when `null`.

**Since:** 0.7
**Default:** `null`

## $smwgPageSpecialProperties

Special properties automatically tracked and stored for pages (e.g. modification date).

**Since:** 1.7
**Default:** `["_MDAT"]`

## $smwgPDefaultType

Internal type ID assumed for properties that have no explicit type declaration; defaults to `Type:Page`.

**Since:** 1.1.2
**Default:** `"_wpg"`

## $smwgPlainList

When `true`, `format=list` produces plain lists without HTML markup, restoring pre-3.0 behaviour.

**Since:** 3.1.2
**Default:** `false`

## $smwgPropertyInvalidCharacterList

Characters considered invalid in property labels; annotations using them produce an error.

**Since:** 2.5
**Default:** `["[", "]", "|", "<", ">", "{", "}", "+", "–", "%", "\r", "\n", "?", "*", "!"]`

## $smwgPropertyLowUsageThreshold

Usage count below which a property is highlighted as "hardly used" on Special:Properties.

**Since:** 1.9
**Default:** `5`

## $smwgPropertyReservedNameList

Names reserved as property names because they interfere with SMW or MediaWiki internals.

**Since:** 3.0
**Default:** `["Category", "smw-property-reserved-category"]`

## $smwgPropertyRetiredList

Property prefixes / IDs marked as retired and eligible for removal from the entity table.

**Since:** 3.1
**Default:** `["_SF_", "_SD_"]`

## $smwgPropertyZeroCountDisplay

When `true`, properties with zero usage are shown on Special:Properties.

**Since:** 1.9
**Default:** `true`

## $smwgQComparators

Pipe-delimited list of comparator operators supported in queries.

**Since:** 1.0
**Default:** `"<|>|!~|!|~|≤|≥|<<|>>|~=|like:|nlike:|in:|not:|phrase:"`

## $smwgQConceptCacheLifetime

Concept cache lifetime in minutes; SMW recomputes an older cache if this threshold is exceeded.

**Since:** 2.1
**Default:** `1440`

## $smwgQConceptMaxDepth

Maximum property-chain depth for a concept query.

**Since:** 2.1
**Default:** `8`

## $smwgQConceptMaxSize

Maximum number of conditions permitted in a concept query.

**Since:** 2.1
**Default:** `20`

## $smwgQDefaultLimit

Default number of result rows returned by an inline query.

**Since:** 1.0
**Default:** `50`

## $smwgQDefaultLinking

Default linking behaviour for query results; one of `none`, `subject`, or `all`.

**Since:** 1.0
**Default:** `"all"`

## $smwgQDefaultNamespaces

Default namespaces searched by queries; `null` disables namespace restrictions for faster queries.

**Since:** 1.0
**Default:** `null`

## $smwgQEnabled

Master switch to enable or disable all query-related features and interfaces.

**Since:** 1.0
**Default:** `true`

## $smwgQExpensiveExecutionLimit

Maximum number of expensive `#ask` / `#show` calls per page; `false` means no limit.

**Since:** 3.0
**Default:** `false`

## $smwgQExpensiveThreshold

Time in seconds above which a `#ask` / `#show` call is classified as expensive.

**Since:** 3.0
**Default:** `10`

## $smwgQFilterDuplicates

When `true`, filters duplicate query segments from the query build process (experimental).

**Since:** 2.5
**Default:** `false`

## $smwgQMaxDepth

Maximum property-chain depth of a query.

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

Maximum number of conditions permitted in a single query.

**Since:** 1.0
**Default:** `16`

## $smwgQPrintoutLimit

Maximum number of printout columns (`?`-statements) supported in a single query.

**Since:** 1.0
**Default:** `100`

## $smwgQStrictComparators

Canonical spelling of `smwStrictComparators`; same semantics.

**Since:** 1.5.3
**Default:** `false`

## $smwgQSubcategoryDepth

Maximum depth for sub-category inclusion steps within the category hierarchy.

**Since:** 1.0
**Default:** `10`

## $smwgQSubpropertyDepth

Maximum depth for sub-property inclusion steps within the property hierarchy.

**Since:** 1.0
**Default:** `10`

## $smwgQTemporaryTablesAutoCommitMode

When `true`, forces auto-commit for temporary tables to work around MySQL GTID restrictions.

**Since:** 2.5
**Default:** `false`

## $smwgQueryDependencyPropertyExemptionList

Property keys excluded from query-dependency detection to avoid spurious cache purges.

**Since:** 2.3
**Default:** `["_MDAT", "_SOBJ", "_ASKDU", "_ASKDE", "_ASKSI", "_ASKFO", "_ASKST"]`

## $smwgQueryProfiler

When `false`, disables the query profiler; can be set to a bitmask of `SMW_QPRFL_*` constants for granular control.

**Since:** 1.9
**Default:** `true`

## $smwgQueryResultCacheLifetime

Lifetime in seconds for embedded query result caches; default one week.

**Since:** 2.5
**Default:** `604800`

## $smwgQueryResultCacheRefreshOnPurge

When `true`, embedded query result caches are invalidated when an `action=purge` event fires.

**Since:** 2.5
**Default:** `true`

## $smwgQueryResultNonEmbeddedCacheLifetime

Lifetime in seconds for non-embedded (Special:Ask, API) query result caches; `0` disables.

**Since:** 2.5
**Default:** `600`

## $smwgQuerySources

Available external query sources; unknown sources fall back to the local wiki.

**Since:** 1.4.3
**Default:** `[]`

## $smwgQUpperbound

Maximum rows printable in an inline query when an offset is applied.

**Since:** 2.1
**Default:** `5000`

## $smwgQUseLegacyQuery

When `true`, reverts to the pre-7.x `SELECT DISTINCT` query shape instead of the derived-table rewrite.

**Since:** 7.0.0
**Default:** `false`

## $smwgSearchByPropertyFuzzy

Type IDs for which Special:SearchByProperty displays nearby fuzzy results when exact matches are few.

**Since:** 2.1
**Default:** `["_num", "_txt", "_dat", "_mlt_rec"]`

## $smwgSetParserCacheKeys

Session keys added to the parser cache key, each causing additional cache fragmentation.

**Since:** 5.1
**Default:** `["userlang", "dateformat"]`

## $smwgSetParserCacheTimestamp

When `true`, sets a timestamp on `ParserOutput` to allow immediate parser-cache invalidation.

**Since:** 5.1
**Default:** `true`

## $smwgSimilarityLookupExemptionProperty

Property whose annotation exempts a property from the similarity lookup.

**Since:** 2.5
**Default:** `"owl:differentFrom"`

## $smwgSparqlCustomConnector

Custom SPARQL repository connector class used when `smwgSparqlRepositoryConnector` is set to `custom`.

**Since:** 2.0
**Default:** `"\\SMW\\SPARQLStore\\RepositoryConnectors\\GenericRepositoryConnector"`

## $smwgSparqlDefaultGraph

Default graph URI for the SPARQL repository, analogous to a database name in relational stores.

**Since:** 1.7
**Default:** `""`

## $smwgSparqlReplicationPropertyExemptionList

Properties exempted from the SPARQL replication process.

**Since:** 2.5
**Default:** `[]`

## $smwgSparqlRepositoryConnector

Pre-deployed SPARQL repository connector to use (`default`, `4store`, `blazegraph`, `fuseki`, `sesame`, `virtuoso`, or `custom`).

**Since:** 2.0
**Default:** `"default"`

## $smwgSupportSectionTag

When `true`, enables `<section>…</section>` tag support.

**Since:** 3.0
**Default:** `true`

## $smwgTranslate

(Disabled feature) When `true`, translates browser labels using interwiki links.

**Since:** 0.7
**Default:** `false`

## $smwgUpgradeKey

Version key that verifies a correct upgrade path was run against the DB schema.

**Since:** 3.0
**Default:** `"smw:2020-04-18"`

## $smwgURITypeSchemeList

URI schemes accepted by the URI datatype.

**Since:** 3.0
**Default:** `["http", "https", "mailto", "tel", "ftp", "sftp", "news", "file", "urn", "telnet", "ldap", "gopher", "ssh", "git", "irc", "ircs"]`

## $smwgUseComparableContentHash

When `true`, normalises subobject content hashes so property-value order does not affect hash equality.

**Since:** 3.0
**Default:** `true`
