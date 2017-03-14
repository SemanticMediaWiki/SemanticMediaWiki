# Semantic MediaWiki 2.5

Released on March 14, 2017.

## Highlights

### Full-text search support

Support for [full-text search](https://www.semantic-mediawiki.org/wiki/Help:Full-text_search) was added using the native capabilities of the SQL backends "MySQL"/"MariaDB" (#1481) and "SQLite" (#1801) for the "Text", "URL" and "Page" datatypes.

### Provenance data recording

Qualifying facts using a simple [provenance model](https://www.semantic-mediawiki.org/wiki/Reference_and_provenance_data) is now supported (#1808) using existing mechanisms in defining a property specification together with a new ["Reference" datatype](https://www.semantic-mediawiki.org/wiki/Help:Type_Reference) ([video](https://youtu.be/t045qkf4YAo)).

### Property chain and language filter support in print request

[Property chain](https://www.semantic-mediawiki.org/wiki/Property_chains_and_paths) for conditions (e.g `[[Located in.Capital of::Foo]]`) was provided for some time, and now got extended (#1824) to supporting the syntax on print requests to retrieve values of a chain member that represent a page node. Values of datatype "Monolingual Text" can now use a language filter (#2037) to restrict the display of a value in a print request.

### Edit protection

[Edit protection](https://www.semantic-mediawiki.org/wiki/Edit_protection) to help avoid changes to properties or other data sensitive pages from alterations that may cause data invalidations (e.g. change of a property type, inconsistent specifications etc.) or process disruptions. This feature integrates with MediaWiki's page protection functionality.

### Preferred property label support

Semantic MediaWiki now supports the declaration of [preferred property labels](https://www.semantic-mediawiki.org/wiki/Preferred_property_label) (#1865) with the objective to show labels in a user context on special pages, query results, and factboxes instead of the canonical property label.

### Query result cache

[Caching of query results](https://www.semantic-mediawiki.org/wiki/Query_result_cache) (#1251) was added as experimental feature to minimize a possible impact of query processing during and after a page view. This change also includes a reevaluation (#2099, #2176) of the query hash (used as identifier) to ensure that cache fragmentation is reduced and duplicate queries can share the same cache across different pages.

### Links in values

Support for [links in values](https://www.semantic-mediawiki.org/wiki/Help:$smwgLinksInValues) for datatype "Text" was extended by use-cases and improved in performance as well as avoiding the former error-prone "PCRE-approach".

### Fixed properties

Support for [fixed properties](https://www.semantic-mediawiki.org/wiki/Help:Fixed_properties) was overhauled, fixed (#2135) and is no longer experimental.

### Special page "SemanticMediaWiki"

Special page ["SemanticMediaWiki"](https://www.semantic-mediawiki.org/wiki/Help:Special:SemanticMediaWiki) formerly known as special page "SMWAdmin" was modernized and extended (#2044, etc.) including a new [configuration setting](https://www.semantic-mediawiki.org/wiki/Help:$smwgAdminFeatures) allowing for a more fine-granded control over feature accessibilty (#2142).

## Compatibility changes

* Minimum requirement for PHP changed to version 5.5 and later
* Minimum requirement for MediaWiki changed to version 1.23 and later (1.27 and later recommended)
* Forward comatibility with MediaWiki 1.29+ adjustments which include #2149, #2198

## Upgrading

This release requires to run `update.php` or `setupStore.php` to add an extra table column for the URI table (#1872) and a new table for the preferred label property (#1865).

## New features and enhancements

* [#1251](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1251) Added support to cache query results
* [#1418](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1418) Added recognition for image formatting options in query results
* [#1481](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1481) Added full-text `MySQL`/`MariaDB` search support to the `SQLStore` (see #1481 for limitations and features supported)
* [#1652](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1652) Added support for approximate search queries that contain a namespace `[[Help:~Abc*]]`
* [#1691](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1691) Added language fallback for special properties
* [#1708](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1708) Added the [External identifier](https://www.semantic-mediawiki.org/wiki/Help:Type_External_identifier) type
* [#1718](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1718) Added feature flag `SMW_DV_NUMV_USPACE` to allow preserving spaces in unit labels
* [#1747](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1747) Extended `InTextAnnotationParser` to check for a possible pipe syntax in combination with `::`
* [#1757](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1757) Added the [`$smwgQTemporaryTablesAutoCommitMode`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQTemporaryTablesAutoCommitMode) setting to mitigate possible issues with temporary tables in `MySQL` for when `enforce_gtid_consistency=true` is set
* [#1756](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1756) Extended the display characteristics of `Special:Browse` to load content via the API back-end (legacy display can be retained by maintaining [`$smwgBrowseByApi`](https://www.semantic-mediawiki.org/wiki/Help:$smwgBrowseByApi) with `false`)
* [#1759](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1759) Improved `Special:Ask` error output
* [#1760](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1760) Improved handling of `MonolingualTextValue` in `Special:SearchByProperty`
* [#1761](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1761) Added language context support in a property page to recognize localized property type `[[Has type ...]]` annotations
* [#1768](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1768) Extended error display to be shown in a user language context
* [#1778](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1778) Export the canonical form of a special page (e.g. `Special:ExportRDF`, `Special:URIResolver`)
* [#1779](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1779) Added [`Special:ProcessingErrorList`](https://www.semantic-mediawiki.org/wiki/Help:Special:ProcessingErrorList)
* [#1793](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1793) Extended date type (`TimeValue`) with an `#LOCL@lang` output format to recognize a specific language tag
* [#1801](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1801) Added `SQLStore` full-text search support for `SQLite`
* [#1802](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1802) Extended parsing in `#set_recurring_event` to avoid a `00:00:00` time display
* [#1809](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1809) Added support for using a property name as index identifier in a print request for the `Record` type
* [#1808](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1808) Added support for recording [provenance data](https://www.semantic-mediawiki.org/wiki/Referenced_statement)
* [#1824](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1824) Added support for the [property chain](https://www.semantic-mediawiki.org/wiki/Property_chain) syntax (e.g. `?SomeProperty.Foo`) in a print request
* [#1838](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1838) Added time zone support in `TimeValue` together with the new `#LOCL#TZ` output format
* [#1854](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1854) Added unescaped output option for `format=json`
* [#1855](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1855) Added `@@@` as special annotation syntax to generate a link to a property (e.g `[[Foo::@@@]]` or `[[Foo::@@@en]]`)
* [#1865](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1865) Added support for [preferred property labels](https://www.semantic-mediawiki.org/wiki/Preferred_property_label)
* [#1872](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1872) Added support for retrieving and storing URIs longer than 255 characters
* [#1875](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1875) Added support for displaying a `title` attribute on tooltips for non JS environments
* [#1891](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1891) Added support for `JSON` typed annotation in `#set` and `#subobject` using the `@json` marker
* [#1927](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1927) Added [`$smwgSubPropertyListLimit`](https://www.semantic-mediawiki.org/wiki/Help:$smwgSubPropertyListLimit) to restrict selection of subproperties on the property page
* [#2007](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2007) Extended the `intro` and `outro` parameter to correctly display parsed links in `Special:Ask`
* [#2024](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2024) Added option `template arguments` in `format=template` to define the type of used parameters
* [#2027](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2027) Extended `format=table` to display an image (instead of a link) in `Special:Ask`
* [#2036](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2036) Added print request option for text values to reduce the length of a text output (e.g. `|?Has text#20`)
* [#2037](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2037) Added `|+lang=` as print request filter to specify a language for a `Monolingual text` result instance
* [#2068](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2068) Extended the `#info` tooltip to work on multiple form sections
* [#2108](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2108) Extended the `smw.dataItem.time` JS component to support historic dates
* [#2109](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2109) Extended `Special:Browse` to distinguish between machine and human generate links
* [#2113](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2113) Extended the [uniqueness constraint](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Has_uniqueness_constraint) to apply a stricter validation on competing annotations
* [#2118](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2118) Added a button to `Special:Ask` to copy the query to the clipboard
* [#2135](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2135) Changed and fixed the behaviour of the [`$smwgFixedProperties`](https://www.semantic-mediawiki.org/wiki/Help:$smwgFixedProperties) setting for [fixed properties](https://www.semantic-mediawiki.org/wiki/Help:Fixed_properties) to ensure consistent typing
* [#2137](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2137) Extended the display of statistics in `Special:Statistics`
* [#2139](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2139) Added the display of Semantic MediaWiki related job statistics under the subsection of the [`Special:SemanticMediaWiki`](https://www.semantic-mediawiki.org/wiki/Help:Special:SemanticMediaWiki) page
* [#2142](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2142) Added `$smwgAdminFeatures` to support `PropertyStatisticsRebuildJob` and `FulltextSearchTableRebuildJob` from the `Special:SemanticMediaWiki` (formally known as `Special:SMWAdmin`) page, the `smwgAdminRefreshStore` setting was deprecated
* [#2153](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2153) Changed the behaviour of the [`$smwgLinksInValues`](https://www.semantic-mediawiki.org/wiki/Help:$smwgLinksInValues) setting to allow using the `Obfuscator` (`SMW_LINV_OBFU`) approach instead of `PCRE` to match links in values (e.g. `[[Has text::[[Lorem ipsum]] dolor sit amet, [[Has page::consectetur adipiscing elit]]]]`)
* [#2157](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2157) Extended the property page to show [redirects (synonyms)](https://www.semantic-mediawiki.org/wiki/Redirects) directly
* [#2173](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2173) Added support for prettified `JSON` output in the `CodeStringValueFormatter`
* [#2176](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2176) Added an experimental feature [`smwgQFilterDuplicates`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQFilterDuplicates) to filter duplicate query segments
* [#2204](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2204) Extended `Special:UnusedProperties` and `Special:WantedProperties` to provide an input form
* [#2207](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2207) Added [`smwgExportResourcesAsIri`](https://www.semantic-mediawiki.org/wiki/Help:$smwgExportResourcesAsIri) to allow exporting resources as IRIs
* [#2209](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2209) Extended parsing of interface messages to support additional `smwgEnabledSpecialPage` pages
* [#2221](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2221) Added possibility to show a general message on each property page (`smw-property-introductory-message`) or for a specific type of property (`smw-property-introductory-message-user`, `smw-property-introductory-message-special`)
* [#2227](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2227) Added warning, error, and info messages for incomplete requirements on a property page
* [#2232](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2232) Added support for [Is edit protected](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Is_edit_protected) property together with `$wgRestrictionLevels` (#2249)
* [#2243](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2243) Added property and concept namespace to the `$wgContentNamespaces` and `$wgNamespacesToBeSearchedDefault` setting
* [#2244](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2244) Added [`Special:PropertyLabelSimilarity`](https://www.semantic-mediawiki.org/wiki/Help:Special:PropertyLabelSimilarity) to help reporting syntactic similarities between property labels
* [#2253](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2253) Added `#-hl` output formatting option to highlight search tokens within a result set
* [#2270](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2270) Added query parameters recording in the [query profiler](https://www.semantic-mediawiki.org/wiki/Help:Query_profiler)
* [#2281](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2281) Added check to detect a divergent type specification for an imported vocabulary
* [#2282](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2282) Added [`$smwgPropertyInvalidCharacterList`](https://www.semantic-mediawiki.org/wiki/Help:$smwgPropertyInvalidCharacterList) to define character validation rules for property labels
* [#2285](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2285) Added [`SMW_HTTP_DEFERRED_SYNC_JOB`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEnabledHttpDeferredJobRequest) option to execute secondary updates synchronously
* [#2289](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2289) Added a [contents importer](https://www.semantic-mediawiki.org/wiki/Help:Contents_importer) to support importing of additional data during the setup process
* [#2290](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2290) Added [query reference](https://www.semantic-mediawiki.org/wiki/Query_reference) links section to `Special:Browse`
* [#2295](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2295) Added [`Allows value list`](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Allows_value_list) to maintain a list of allowed values using a `NS_MEDIAWIKI` reference page
* [#2301](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2301) Added [`$smwgSparqlReplicationPropertyExemptionList`](https://www.semantic-mediawiki.org/wiki/Help:$smwgSparqlReplicationPropertyExemptionList) to suppress replication for selected properties to a `SPARQL` endpoint
* [#2325](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2325) Added `#-ia` as print request output option for the text datatype
* [#2331](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2331) Added [`$smwgResultFormatsFeatures`](https://www.semantic-mediawiki.org/wiki/Help:$smwgResultFormatsFeatures) to control available features for specific `ResultFormatter` and includes (`SMW_RF_TEMPLATE_OUTSEP` to support the #2022 changes)
* Many new translations for numerous languages by the communtity of [translatewiki.net](https://translatewiki.net/w/i.php?title=Special%3AMessageGroupStats&x=D&group=mwgithub-semanticmediawiki&suppressempty=1)
* New translation for special properties, datatypes, magic words, date formats and aliases for Catalan and German by Semantic MediaWiki community members

## Bug fixes

* [#1258](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1258) Fixed "named args" parameter use in further results link
* [#1328](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1328) Fixed a "Undefined index: language" error in `#smwdoc` parser function
* [#1419](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1419) Fixed Feed result printer ouput for empty results
* [#1709](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1709) Fixed a potential "Lock wait timeout exceeded; try restarting transaction" in connection with `--procs`
* [#1713](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1713) Fixed a "Segmentation fault" for when `QueryResultDependencyListResolver` tries to resolve a category/property hierarchy with a circular reference
* [#1715](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1715) Fixed decoding of a single quotation mark in `DisplayTitlePropertyAnnotator`
* #1724 Fixed a possible `InvalidArgumentException` in connection with `SMW_DV_PVUC` by updating the `CachedPropertyValuesPrefetcher` version number
* #1727 Fixed an issue when property names contain `<` or `>` symbols
* #1728 Fixed fatal error in `Special:SearchByProperty` on when the property name contains invalid characters
* #1731 Fixed possible error in the `SkinAfterContent` hook when a null object is used
* #1744 Fixed special page "Searchbyproperty" not working correctly with "-" sign
* #1775 Fixed time offset recognition
* #1817 Disabled `DataValue` constraint validation when used in a query context
* #1823 Fixed annotation of `Display title of` when `SMW_DV_WPV_DTITLE` is disabled
* #1880 Fixed handling of the `bytea` type in `postgres` for a blob field
* #1886 Fixed disappearance of the `Property` namespace in connection with extensions that use `wfLoadExtension`
* #1922 Fixed `InfoLinksProvider` to avoid `LOCL` info links
* #1926 Fixed `PrintRequest` to recognize the spant tag in labels
* #1935 Fixed "Error: 42P10 ERROR: ... ORDER BY expressions must appear in select list" for PostgreSQL
* #1957 Fixed `SMWSQLStore3Writers::getSubobjects` using the wrong DBKey in case of predefined properties
* [#1963](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1963) Fixed by relying on #2153
* #1977 Fixed Unexpected general modules for Resource Loader
* #1978 Fixed `Tablebuilder` to avoid index creation on an unaltered schema definition
* #1985 Fixed a potential fatal error in `MaintenanceLogger` for when `$wgMaxNameChars` doesn't match an expected name length
* [#2000](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2000) Fixed label and caption sanitization
* [#2022](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2022) Fixed the usage of the sep parameter for format "template"
* #2061 Fixed strict comparison `===` for strings in `PropertyTableRowDiffer`
* #2070 Filter invalid entity display from `Special:Concepts`
* #2071 Prevent extensions to register already known canonical property labels and hereby avoid a possible ID mismatch
* #2076 Fixed issue for Gregorian and Julian calendars having a year 0
* #2078 Fixed issue with "SELECT list; this is incompatible with DISTINCT" for MySQL 5.7+
* #2089 Fixed issue with "UPDATE - SET; Data too long for column" for MySQL 5.7+
* #2093 Avoid removal of existing data by #REDIRECT in target
* #2107 Fixed `NamespaceManager::init` to set SMW_NS* default settings
* #2127 Fixed a call to a the member function `getHash()` on nulll
* [#2182](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2182) Fixed display of special properties in `Special:UnusedProperties`
* [#2183](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2183) Fixed display of properties with no explicit datatype in `Special:UnusedProperties`
* #2188 Fixed error in special page "RDFExport" with non-latin instance names
* #2202 Added guard against error "Invalid or virtual namespace -1 given"
* [#2228](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2228) Fixed text output for the table format in `Special:Ask`
* [#2294](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2294) Avoid a possible `Parser::lock` during an `UpdateJob`

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

## Settings and configurations

[Settings and configurations](https://www.semantic-mediawiki.org/w/index.php?title=Special:Ask&x=-5B-5BHas-20configuration%3A%3A%2B-5D-5D-20-5B-5BHas-20minimum-20version%3A%3A2.5.0-5D-5D%2F-3FHas-20configuration-20parameter-20name%3DConfiguration-20parameter%2F-3FHas-20description%3DDescription&format=broadtable&limit=50&link=all&headers=show&mainlabel=-&searchlabel=...%20further%20results&class=sortable%20wikitable%20smwtable&offset=) added with 2.5.0.

## Contributors

* 688 - James Hong Kong
* 59 - Karsten Hoffmeyer
* 51 - Jeroen De Dauw
* 37 - Niklas Laxström
* 14 - translatewiki.net
* 5 - Maciej Brencz
* 4 - Felipe de Jong
* 4 - Siebrand Mazeland
* 2 - Alex Winkler
* 2 - Stephan Gambke
* 2 - Toni Hermoso Pulido
* 1 - Amir E. Aharoni
* 1 - Felipe Schenone
* 1 - Jaider Andrade Ferreira
* 1 - James Forrester
* 1 - Justin Du
* 1 - Sébastien Beyou
* 1 - Virginia Cepeda
