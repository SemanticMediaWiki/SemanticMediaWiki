# Semantic MediaWiki 2.5

THIS IS NOT A RELEASE YET

## Highlights

* #1481/#1801
* #1808
* #1824

## Compatibility changes

* ...

## New features and enhancements

* #1481 Enhanced the `SQLStore` to support full-text searches provided by the `MySQL`/`MariaDB` back-end (see #1481 for limitations and features supported)
* #1652 Added support for approximate queries that contain a namespace `[[Help:~Abc*]]`
* #1708 Added the [External identifier](https://www.semantic-mediawiki.org/wiki/Help:Type_External_identifier) type
* #1718 Added feature flag `SMW_DV_NUMV_USPACE` to allow preserving spaces in unit labels
* #1747 Extended `InTextAnnotationParser` to check for a possible pipe syntax in combination with `::` 
* #1759 Improved `Special:Ask` error output 
* #1760 Improved handling of `MonolingualTextValue` in `Special:SearchByProperty`
* #1758 Added the [`$smwgQTemporaryTablesAutoCommitMode`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQTemporaryTablesAutoCommitMode) setting to mitigate possible issues with temporary tables in `MySQL` for when `enforce_gtid_consistency=true` is set
* #1756 Extended the display characteristics of `Special:Browse` to load content via the API back-end (legacy display can be retained by setting [`$smwgBrowseByApi`](https://www.semantic-mediawiki.org/wiki/Help:$smwgBrowseByApi) to `false`) 
* #1761 Added content language context to recognize localized property type `[[Has type ...]]` annotations
* #1764 Added `--with-maintenance-log` option to the "rebuildFulltextSearchTable.php" maintenance script
* #1768 Improved general error display to be more user context friendly
* #1779 Added [`Special:ProcessingErrorList`](https://www.semantic-mediawiki.org/wiki/Help:Special:ProcessingErrorList) 
* #1793 Extended `#LOCL` support for the date type (`TimeValue`)
* #1801 Added `SQLStore` full-text search support for `SQLite`
* #1802 Improved `#set_recurring_event` to avoid displaying a `00:00:00` time 
* #1809 Added support for using a property name as index identifier in the `Record` type
* #1808 Added support for recording [provenance data](https://www.semantic-mediawiki.org/wiki/Referenced_statement)
* #1824 Added support for the property chain syntax in printrequests
* #1838 Added `Timezone` support for the `LOCL` output format 
* #1854 Added unescaped output support for `format=json`
* #1855 Added `@@@` as special annotation syntax to generate a property link (e.g `[[Foo::@@@]]`)

## Bug fixes

* #1328 Fixed a "Undefined index: language" error in `#smwdoc` parser function
* #1713 Fixed a "Segmentation fault" when `QueryResultDependencyListResolver` tries to resolve a category/property hierarchy with a circular reference
* #1715 Fixed decoding of a single quotation mark in `DisplayTitlePropertyAnnotator`
* #1724 Fixed a possible `InvalidArgumentException` in connection with `SMW_DV_PVUC` by updating the `CachedPropertyValuesPrefetcher` version number
* #1727 Fixed an issue when property names contain `<` or `>` symbols 
* #1728 Fixed fatal error in `Special:SearchByProperty` on when the property name contains invalid characters
* #1731 Fixed possible error in the `SkinAfterContent` hook when a null object is used
* #1775 Fixed time offset recognition 
* #1817 Disabled `DataValue` constraint validation when used in a query context 
* #1823 Fixed annotation of `Display title of` when `SMW_DV_WPV_DTITLE` is disabled
* #1880 Fixed handling of the `bytea` type in `postgres` for a blob field

## Internal changes

* #1511 Removed I18n shim originally required for MediaWiki < 1.23
* #1726 Allows `QueryDependencyLinksStore` to execute `getDependencyListByLateRetrieval` even in cases of an intial empty list
* #1750 Added `RdbmsTableBuilder` to replace `SMWSQLHelpers`
* #1780 Added `ResourceBuilder` and `DispatchingResourceBuilder`
* #1791 Added `PropertyRegistry::registerPropertyDescriptionByMsgKey`
* #1776 Added `QueryEngine` and `StoreAware` interface

## Contributors

* ...
