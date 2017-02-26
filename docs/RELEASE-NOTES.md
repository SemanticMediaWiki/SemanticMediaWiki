# Semantic MediaWiki 2.5

THIS IS NOT A RELEASE YET

## Highlights

### Full-text support

The #1481 (`MySQL`/`MariaDB`) and #1801 (`SQLite`) PR added [full-text](https://www.semantic-mediawiki.org/wiki/Help:Full-text_search) support using the native capabilities of the SQL backend.

### Provenance data recording

Qualifying facts using a simple [provenance model](https://www.semantic-mediawiki.org/wiki/Reference_and_provenance_data) is now supported #1808 using existing mechanisms in defining a property specification together with a new [Reference type](https://www.semantic-mediawiki.org/wiki/Help:Type_Reference) ([video](https://youtu.be/t045qkf4YAo)).

### Property chain and language filter support in print request

[Property chain](https://www.semantic-mediawiki.org/wiki/Property_chains_and_paths) for conditions (e.g `[[Located in.Capital of::Foo]]`) was provided for some time, and #1824 extends the support for the syntax on print requests to retrieve values of a chain member that represent a page node. Values of type `MonolingualText` can now use a language filter (#2037) to restrict the display of a value in a print request.

### Preferred property label support

Semantic MediaWiki now supports the declaration of [preferred property labels](https://www.semantic-mediawiki.org/wiki/Preferred_property_label) (#1865) with the objective to show labels in a user context on special pages, query results, and factboxes instead of the canonical property label.

### Query result cache

An experimental feature (#1251) to support caching of query results and hereby minimize a possible impact of query processing during and after a page view. This change also includes a reevaluation (#2099, #2176) of the query hash (used as identifier) to ensure that cache fragmentation is reduced and duplicate queries can share the same cache across different pages.

* #2135

## Compatibility changes

* Requires to run `update.php` to add an extra table column for the URI table (#1872) and a new table for the preferred label property (#1865).
* 1.29+ adjustments which includes #2149, #2198

## New features and enhancements

* [#1251](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1251) Added support to cache query results
* [#1418](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1418) Added recognition for image formatting options in query results
* [#1481](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1481) Added full-text `MySQL`/`MariaDB` search support to the `SQLStore` (see #1481 for limitations and features supported)
* [#1652](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1652) Added support for approximate search queries that contain a namespace `[[Help:~Abc*]]`
* [#1691](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1691) Added language fallback for special properties
* [#1708](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1708) Added the [External identifier](https://www.semantic-mediawiki.org/wiki/Help:Type_External_identifier) type
* [#1718](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1718) Added feature flag `SMW_DV_NUMV_USPACE` to allow preserving spaces in unit labels
* [#1747](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1747) Extended `InTextAnnotationParser` to check for a possible pipe syntax in combination with `::`
* [#1757] Added the [`$smwgQTemporaryTablesAutoCommitMode`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQTemporaryTablesAutoCommitMode) setting to mitigate possible issues with temporary tables in `MySQL` for when `enforce_gtid_consistency=true` is set
* [#1756](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1756) Extended the display characteristics of `Special:Browse` to load content via the API back-end (legacy display can be retained by maintaining [`$smwgBrowseByApi`](https://www.semantic-mediawiki.org/wiki/Help:$smwgBrowseByApi) with `false`)
* [#1759](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1759) Improved `Special:Ask` error output
* [#1760](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1760) Improved handling of `MonolingualTextValue` in `Special:SearchByProperty`
* [#1761](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1761) Added language context support in a property page to recognize localized property type `[[Has type ...]]` annotations
* [#1768](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1768) Extended error display to be shown in a user language context
* [#1779](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1779) Added [`Special:ProcessingErrorList`](https://www.semantic-mediawiki.org/wiki/Help:Special:ProcessingErrorList)
* [#1793](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1793) Extended date type (`TimeValue`) with an `#LOCL@lang` output format to recognize a specific language tag
* [#1801](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1801) Added `SQLStore` full-text search support for `SQLite`
* [#1802](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1802) Extended parsing in `#set_recurring_event` to avoid displaying a `00:00:00` time
* [#1809](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1809) Added support for using a property name as index identifier in a print request for the `Record` type
* [#1808](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1808) Added support for recording [provenance data](https://www.semantic-mediawiki.org/wiki/Referenced_statement)
* [#1824](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1824) Added support for the property chain syntax (e.g. `?SomeProperty.Foo) in a print request
* [#1838](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1838) Added time zone support in `TimeValue` together with a `#LOCL#TZ` output format
* [#1854](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1854) Added unescaped output option for `format=json`
* [#1855](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1855) Added `@@@` as special annotation syntax to generate a property link (e.g `[[Foo::@@@]]` or `[[Foo::@@@en]]`)
* [#1865](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1865) Added support for preferred property labels using `MonolingualTextValue` to contain a language tag
* [#1872](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1872) Added support for retrieving and store URI's longer than 255 chars
* [#1875](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1875) Added support for displaying a `title` on tooltips for non JS environments
* [#1891](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1891) Added support for JSON typed annotation in `#set` and `#subobject` using the `@json` marker
* [#1927](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1927) Added `$smwgSubPropertyListLimit` to restrict selection of subproperties on the property page
* [#2007](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2007) Extended `intro` and `outro` parameter to display parsed links in `Special:Ask`
* [#2024](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2024) Extended `format=template` with option `template arguments` to select a type of used parameters
* [#2027](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2027) Extended `format=table` to display an image (instead of a link) in `Special:Ask`
* [#2036](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2036) Added output formatting option for text values to reduce the length of a text output (e.g. `|?Has text#20`)
* [#2037](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2037) Added support for the `|+lang=` printout filter to be applied to the `Monolingual text` type in order to filter a specific language result set
* [#2068](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2068) Extended the `#info` tooltip to work on multiple form sections
* [#2108](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2108) Extended the`smw.dataItem.time` JS component to support historic dates
* [#2109](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2109) Extended `Special:Browse` to distinguish between machine and human generate links
* [#2113](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2113) Extended `Has uniqueness constraint` to use a stricter validation on competing annotations
* [#2118](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2118) Added a convenience button to `Special:Ask` allowing a query to be copied to the clipboard
* [#2135](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2135) Changed and fixed the behaviour of the `$smwgFixedProperties` setting to only define the property label of an expected [fixed property](https://www.semantic-mediawiki.org/wiki/Help:Fixed_properties) to ensure consistent typing of property
* [#2137](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2137) Extended the display of statistics in `Special:Statistics` with the total properties count
* [#2139](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2139) Added the display of Semantic MediaWiki related job statistics under the subsection of the `Special:SemanticMediaWiki` page
* [#2142](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2142) Added `$smwgAdminFeatures` to support `PropertyStatisticsRebuildJob` and `FulltextSearchTableRebuildJob` from the `Special:SemanticMediaWiki` (formally known as `Special:SMWAdmin`) page, `smwgAdminRefreshStore` was deprecated
* [#2153](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2153) Changed behaviour of the `$smwgLinksInValues` setting to allow using the `Obfuscator` (`SMW_LINV_OBFU`) instead of `PCRE` to match links in values (e.g. `[[Has text::[[Lorem ipsum]] dolor sit amet, [[Has page::consectetur adipiscing elit]]]]`)
* [#2157](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2157) Extended property page to show [redirects (synonyms)](https://www.semantic-mediawiki.org/wiki/Redirects)
* [#2173](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2173) Added support for pretty JSON output in the `CodeStringValueFormatter`
* [#2176](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2176) Added experimental feature (`smwgQFilterDuplicates`) to filter duplicate query segments
* [#2204](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2204) Extended `Special:UnusedProperties` and `Special:WantedProperties` to provide a input form
* [#2207](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2207) Added `smwgExportResourcesAsIri` to allow exporting resources as IRIs
* [#2209](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2209) Extended parsing of interface messages to support additional `smwgEnabledSpecialPage`
* [#2221](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2221) Added possibility to show a general message on each property page (`smw-property-introductory-message`) or for a specific type of property (`smw-property-introductory-message-user`, `smw-property-introductory-message-special`)
* [#2227](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2227) Added warning, error, and info messages for incomplete requirements on a property specification
* [#2232](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2232) Added support for "Is edit protected" together with `$wgRestrictionLevels` (#2249)
* [#2243](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2243) Added property and concept namespace to the `$wgContentNamespaces`/`$wgNamespacesToBeSearchedDefault` setting
* [#2244](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2244) Added `Special:PropertyLabelSimilarity` to help reporting of similarities in property labels
* [#2253](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2253) Added `#-hl` output formatting option to highlight search tokens in a result set
* [#2270](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2270) Added query parameters recording in the [query profiler](https://www.semantic-mediawiki.org/wiki/Help:Query_profiler)
* [#2281](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2281) Added check to detect a divergent type specification for an imported declaration
* [#2282](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2282) Added `$smwgPropertyInvalidCharacterList` for a stricter naming validation of property labels
* #2285
* [#2290](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2290) Added [query reference](https://www.semantic-mediawiki.org/wiki/Query_reference) links section to `Special:Browse`

## Bug fixes

* [#1258](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1258) Fixed "named args" parameter use in further results link
* [#1328](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1328) Fixed a "Undefined index: language" error in `#smwdoc` parser function
* [#1709](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1709) Fixed a potential "Lock wait timeout exceeded; try restarting transaction" in connection with `--procs`
* [#1713](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1713) Fixed a "Segmentation fault" for when `QueryResultDependencyListResolver` tries to resolve a category/property hierarchy with a circular reference
* [#1715](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1715) Fixed decoding of a single quotation mark in `DisplayTitlePropertyAnnotator`
* #1724 Fixed a possible `InvalidArgumentException` in connection with `SMW_DV_PVUC` by updating the `CachedPropertyValuesPrefetcher` version number
* #1727 Fixed an issue when property names contain `<` or `>` symbols
* #1728 Fixed fatal error in `Special:SearchByProperty` on when the property name contains invalid characters
* #1731 Fixed possible error in the `SkinAfterContent` hook when a null object is used
* #1744
* #1775 Fixed time offset recognition
* #1817 Disabled `DataValue` constraint validation when used in a query context
* #1823 Fixed annotation of `Display title of` when `SMW_DV_WPV_DTITLE` is disabled
* #1880 Fixed handling of the `bytea` type in `postgres` for a blob field
* #1886 Fixed disappearance of the `Property` namespace in connection with extensions that use `wfLoadExtension`
* #1922
* #1926
* #1935
* #1957
* [#1963](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1963) Fixed by relying on #2153
* #1977
* #1978 Fixed `Tablebuilder` to avoid index creation on an unaltered schema definition
* #1985 Fixed a potential fatal error in `MaintenanceLogger` for when `$wgMaxNameChars` doesn't match an expected name length
* [#2000](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2000) Fixed label and caption sanitization
* #2022
* #2061 Fixed strict comparison `===` for strings in `PropertyTableRowDiffer`
* #2070 Filter invalid entity display from `Special:Concepts`
* #2071 Prevent extensions to register already known canonical property labels and hereby avoid a possible ID mismatch
* #2076
* #2078
* #2089
* #2093
* #2107
* #2127
* [#2182](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2182) Fixed display of special properties in `Special:UnusedProperties`
* [#2183](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2183) Fixed display of properties with no explicit datatype in `Special:UnusedProperties`
* #2188
* #2202
* [#2228](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2228) Fixed text output for the table format in `Special:Ask`

## Internal changes

* #1511 Removed I18n shim originally required for MediaWiki < 1.23
* #1726 Allows `QueryDependencyLinksStore` to execute `getDependencyListByLateRetrieval` even in cases of an intial empty list
* #1750 Added `TableBuilder` to replace `SMWSQLHelpers`
* #1780 Added `ResourceBuilder` and `DispatchingResourceBuilder`
* #1791 Added `PropertyRegistry::registerPropertyDescriptionByMsgKey`
* #1776 Added `QueryEngine` and `StoreAware` interface
* [#1848](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1848) Added `ExtraneousLanguage` to handle Semantic MediaWiki specific `i18n` content in a `JSON` format, removed the `PHP` language files
* #1940 Added `Installer` and `TableSchemaManager` to replace `SMWSQLStore3SetupHandlers`
* [#2118](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2118) Added the `onoi/shared-resources~0.3` dependency
* [#2201](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2201) Changed normalization of spaces to `_` instead of `%20` in `DIUri`
* [#2214](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2214) Added `LinksProcessor` and `SemanticLinksParser`
* [#2217](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2217) Added `QuerySegmentListBuildManager`
* [#2275](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2275) Added the `onoi/callback-container:~2.0` dependency
* [#2282](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2282) Added `DataValueServiceFactory` and `DataValueServices.php` to allow injection of services into a `DataValue` instance

## Settings changes

* Added [$smwgSimilarityLookupExemptionProperty](https://www.semantic-mediawiki.org/wiki/Help:$smwgSimilarityLookupExemptionProperty)
* Added [$smwgPropertyInvalidCharacterList](https://www.semantic-mediawiki.org/wiki/Help:$smwgPropertyInvalidCharacterList)
* Added [$smwgEditProtectionRight](https://www.semantic-mediawiki.org/wiki/Help:$smwgEditProtectionRight)
* Added [$smwgQueryResultCacheRefreshOnPurge](https://www.semantic-mediawiki.org/wiki/Help:$smwgQueryResultCacheRefreshOnPurge)
* Added [$smwgQueryResultNonEmbeddedCacheLifetime](https://www.semantic-mediawiki.org/wiki/Help:$smwgQueryResultNonEmbeddedCacheLifetime)
* Added [$smwgQueryResultCacheLifetime](https://www.semantic-mediawiki.org/wiki/Help:$smwgQueryResultCacheLifetime)
* Added [$smwgQueryResultCacheType](https://www.semantic-mediawiki.org/wiki/Help:$smwgQueryResultCacheType)
* Added [$smwgQTemporaryTablesAutoCommitMode](https://www.semantic-mediawiki.org/wiki/Help:$smwgQTemporaryTablesAutoCommitMode)
* Added [$smwgQFilterDuplicates](https://www.semantic-mediawiki.org/wiki/Help:$smwgQFilterDuplicates)
* Added [$smwgFulltextSearchMinTokenSize](https://www.semantic-mediawiki.org/wiki/Help:$smwgFulltextSearchMinTokenSize)
* Added [$smwgFulltextSearchIndexableDataTypes](https://www.semantic-mediawiki.org/wiki/Help:$smwgFulltextSearchIndexableDataTypes)
* Added [$smwgFulltextSearchPropertyExemptionList](https://www.semantic-mediawiki.org/wiki/Help:$smwgFulltextSearchPropertyExemptionList)
* Added [$smwgFulltextSearchTableOptions](https://www.semantic-mediawiki.org/wiki/Help:$smwgFulltextSearchTableOptions)
* Added [$smwgFulltextDeferredUpdate](https://www.semantic-mediawiki.org/wiki/Help:$smwgFulltextDeferredUpdate)
* Added [$smwgEnabledFulltextSearch](https://www.semantic-mediawiki.org/wiki/Help:$smwgEnabledFulltextSearch)
* Added [$smwgExportResourcesAsIri](https://www.semantic-mediawiki.org/wiki/Help:$smwgExportResourcesAsIri)
* Added [$smwgDataTypePropertyExemptionList](https://www.semantic-mediawiki.org/wiki/Help:$smwgDataTypePropertyExemptionList)
* Added [$smwgRedirectPropertyListLimit](https://www.semantic-mediawiki.org/wiki/Help:$smwgRedirectPropertyListLimit)
* Added [$smwgSubPropertyListLimit](https://www.semantic-mediawiki.org/wiki/Help:$smwgSubPropertyListLimit)
* Added [$smwgBrowseByApi](https://www.semantic-mediawiki.org/wiki/Help:$smwgBrowseByApi)
* Added [$smwgServicesFileDir](https://www.semantic-mediawiki.org/wiki/Help:$smwgServicesFileDir) (internal use)
* Added [$smwgAdminFeatures](https://www.semantic-mediawiki.org/wiki/Help:$smwgAdminFeatures) and deprecated the [$smwgAdminRefreshStore](https://www.semantic-mediawiki.org/wiki/Help:$smwgAdminRefreshStore) setting
* Added [$smwgPropertyInvalidCharacterList](https://www.semantic-mediawiki.org/wiki/Help:$smwgPropertyInvalidCharacterList)
* Changed [$smwgLinksInValues](https://www.semantic-mediawiki.org/wiki/Help:$smwgLinksInValues) behaviour
* Changed [$smwgFixedProperties](https://www.semantic-mediawiki.org/wiki/Help:$smwgFixedProperties) behaviour

## Contributors

* ...
