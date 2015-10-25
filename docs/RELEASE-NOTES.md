# Semantic MediaWiki 2.3

Released on October 25th, 2015.


## Highlight: Improved SPARQLStore support

SMWs SPARQLStore has now reached full feature parity with the SQLStore. On top of that, various performance improvements where made to the SPARQLStore.

The `$GLOBALS['smwgSparqlQFeatures']` configuration setting now supports these additional values:

* #1001 `SMW_SPARQL_QF_REDI`: enable property/value redirects support in queries
* #1003 `SMW_SPARQL_QF_SUBP`: enable subproperty hierarchy support
* #1012 `SMW_SPARQL_QF_SUBC`: enable subcategory hierarchy support

If your TDB back-end does not support SPARQL 1.1, this setting needs to be set to `SMW_SPARQL_QF_NONE`.

* #1152 Added preference for use of canonical identifiers to support language agnostic category/property statements, (use `$GLOBALS['smwgExportBCNonCanonicalFormUse'] = true` to keep backwards compatibility until 3.x)
* #1158 Added basic support for `_geo` queries to the `SPARQLStore`
* #1159 Added limitation of the `aux` property usage in the Exporter (use `$GLOBALS['smwgExportBCAuxiliaryUse'] = true;` to keep backwards compatibility until 3.x)

## New features and enhancements

* #1042 Added progress indicator to `rebuildData.php`
* #1047 Extended context help displayed on `Special:Types` and subsequent type pages
* #1049 Improved MobileFrontend support
* #1053 Added a `CSS` rule to visually distinguish subobject links from "normal" links
* #1063 Added `$GLOBALS['smwgValueLookupCacheType']` to improve DB lookup performance though the use of a responsive cache layer (such as `redis`) and buffer repeated requests either from the API or page view to the back-end.
* #1066, #1075 It is now possible to use extra double colons in annotations. For instance `[[DOI::10.1002/123::abc]]` or `[[Foo:::123]]`
* #1097 Predefined property aliases are redirected to the base property
* #1107 The template support of #set now includes an automatically added `last-element` parameter
* #1106 Added `--skip-properties` flag to `rebuildData.php`
* #1106 `rebuildData.php` now first removes items marked for deletion
* #1129 Extended `~*` search pattern for `_ema` and `_tel` to allow for searches like `[[Has telephone number::~*0123*]]` and `[[Has email::~*123.org]]`
* #1147 The category result format now supports `columns=0`, which results in automatic column count selection
* #1171 Added SQL EXPLAIN output to the debug result format
* #1172 Added `@category` as parameter with a fixed assignment (`_INST`) to `#subobject`
* #1178 Added `~` and `!~` comparator support for values of type date

## New experimental features

These features are disabled by default and can be turned on using configuration. Additional logging
happens for these features until they mature from being an experimental feature in a future release.

* #1035, #1063 Added `CachedValueLookupStore` as post-cached layer to improve DB read access (`$GLOBALS['smwgValueLookupCacheType']`, $GLOBALS['smwgValueLookupCacheLifetime'])
* #1116 Added $GLOBALS['smwgValueLookupFeatures'] setting to fain grain the cache access level, default is set to `SMW_VL_SD | SMW_VL_PL | SMW_VL_PV | SMW_VL_PS;`
* #1117 Added `EmbeddedQueryDependencyLinksStore` to track query dependencies and update altered queries using `ParserCachePurgeJob` for when `$GLOBALS['smwgEnabledQueryDependencyLinksStore']` is enabled
* #1135 Added `$GLOBALS['smwgPropertyDependencyDetectionBlacklist']` to exclude properties from dependency detection
* #1141 Added detection of property and category hierarchy dependency in `EmbeddedQueryDependencyLinksStore`

## Bug fixes

* #400 (#1222) Fixed `RuntimeException` in `SQLStore` caused by a DI type mismatch during a lookup operation
* #682 Fixed id mismatch in `SQLStore`
* #1005 Fixed syntax error in `SQLStore`(`SQLite`) for temporary tables on disjunctive category/subcategory queries
* #1033 Fixed PHP notice in `JobBase` for non-array parameters
* #1038 Fixed Fatal error: Call to undefined method `SMWDIError::getString`
* #1046 Fixed RuntimeException in `UndeclaredPropertyListLookup` for when a DB prefix is used
* #1051 Fixed call to undefined method in `ConceptDescriptionInterpreter` in `SQLStore`
* #1054 Fixed behavior for `#REDIRECT` to create the same data reference as `Special:MovePage`
* #1059 Fixed usage of `[[Has page::~*a*||~*A*]]` for `SPARQLStore` when `Has page` is declared as page type
* #1060 Fixed usage of `(a OR b) AND (c OR d)` as query pattern for the `SQLStore`
* #1067 Fixed return value of the `#set` parser
* #1074 Fixed duplicated error message for a `_dat` DataValue
* #1081 Fixed mismatch of `owl:Class` for categories when used in connection with a vocabulary import
* #1090 Fixed error on Special:Ask when using a format provided by Semantic Maps
* #1126 Fixed silent annotations added by the `Factbox` when content contains `[[ ... ]]`
* #1120 Fixed resource loading issue on Windows when using `$wgResourceLoaderDebug=true`
* #233 Fixed disabling of `$GLOBALS['wgFooterIcons']['poweredby']['semanticmediawiki']`
* #1137 Fixed re-setting of `smw-admin` user group permission to its default
* #1146 Fixed #set rendering of template supported output (refs #1067)
* #1096 Fixed inverse prefix for predefined properties that caused misinterpret `Concept` queries
* #1166 Fixed context awareness of `ParserAfterTidy` in connection with the `purge` action
* #1165 Fixed "duplicate key value violates unique constraint" for PostgreSQL on conjunctive and disjunctive queries
* #1182 Fixed further link to use the format parameter as specified by `#ask`
* #1207 Fixed usage of the `!~` comparator for properties that have a limited set of allowed values

### Improved handling of removed entities in SQLStore

In previous releases it could happen that deleted entities (subject, property) reappeared in queries even though they have been removed. This release introduces several changes to eliminate some of the issues identified.

* #1100 introduced a deletion marker on entities that got deleted, making them no longer available to queries or special page display.
* #1127 Added `--shallow-update` to `rebuildData.php`, to only parse those entities that have a different last modified timestamp compared to that of the last revision. This enables to run `rebuildData.php` updates on deleted, redirects, and other out of sync entities.
* Solved #701 where an unconditional namespace query `[[Help:+]]` would display deleted subjects (in case those subjects were deleted)
* #1105 Added filter to mark deleted redirect targets with `SMW_SQL3_SMWDELETEIW`
* #1112 Added filter to mark outdated subobjects with `SMW_SQL3_SMWDELETEIW`
* #1151 Added removal of unmatched "ghost" pages in the ID_TABLE

## Internal changes

* #1018 Added `PropertyTableRowDiffer` to simplify computation of `SemanticData` diff's (relates to #682)
* #1039 Added `SemanticData::getLastModified`
* #1041 Added `ByIdDataRebuildDispatcher` to isolate `SMWSQLStore3SetupHandlers::refreshData`
* #1071 Added `SMW::SQLStore::AddCustomFixedPropertyTables` hook to simplify registration of fixed property tables by extensions
* #1068 Added setting to support recursive annotation for selected result formats (refs #1055, #711)
* #1086 Changed redirect update logic to accommodate the manual #REDIRECT (refs #895, #1054)
* Added `SMW::Browse::AfterIncomingPropertiesLookupComplete` which allows to extend the incoming properties display for `Special:Browse`
* Added `SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate` which allows to replace the further result incoming link in `Special:Browse`
* #1078 Renamed `ParserParameterFormatter` to `ParserParameterProcessor` and `ParameterFormatterFactory` to `ParameterProcessorFactory`
* #1102 Added `onoi/http-request:~1.0` dependency
* Decrease chunk size in `UpdateDispatcherJob` (refs #951)
* #1110 Extended `TurtleTriplesBuilder` to split larger turtle sets into chunks
* #1111 Added support for the atomic DB transaction mode to improve the rollback process in case of a DB transaction failure
* #1108 Added `CompositePropertyTableDiffIterator` which for the added `'SMW::SQLStore::AfterDataUpdateComplete'` returns ids that have been updated only (as diff of the update)
* #1119 Added `RequestOptionsProcessor`
* #1130 Added `DeferredRequestDispatchManager` to decouple jobs during an update
* #1133 Fixed MW 1.25/1.26 API tests
* #1145 Added `onoi/callback-container:~1.0` and removes all custom DIC code from SMW-core
* (964155) Added removal of whitespace for `DIBlob` values (" Foo " becomes "Foo")
* #1149 Added `InMemoryPoolCache` to improve performance for the `SPARQLStore` during turtle serialization

## Contributors

**Code contributors**

* MWJames
* Jeroen De Dauw
* Karsten Hoffmeyer (kghbln)
* Felipe de Jong (jongfeli)
* Vitaliy Filippov (vitalif)
* paladox
* Amir E. Aharoni
* Joel K. Pettersson
* umherirrender
* Kunal Mehta (legoktm)
* TranslateWiki.net

**Other contributors**

* yoonghm
* cicalese
* bogota
* plegault3397
