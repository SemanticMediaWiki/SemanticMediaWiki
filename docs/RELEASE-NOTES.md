# Semantic MediaWiki 2.5

THIS IS NOT A RELEASE YET

## Highlights

* #1481

## Compatibility changes

* ...

## New features and enhancements

* #1708 Added the [External identifier](https://www.semantic-mediawiki.org/wiki/Help:Type_External_identifier) type
* #1759 Improved `Special:Ask` error output 
* #1760 Improved handling of `MonolingualTextValue` in `Special:SearchByProperty`
* #1481 Enhanced the `SQLStore` to support fulltext searches provided by the `MySQL`/`MariaDB` back-end (see #1481 for limitations and features supported)
* #1758 Added the [`$smwgQTemporaryTablesAutoCommitMode`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQTemporaryTablesAutoCommitMode) setting to mitigate possible issues with temporary tables in `MySQL` for when `enforce_gtid_consistency=true` is set
* #1756 Extended the display characteristics of `Special:Browse` to load content via the API back-end (legacy display can be retained by setting [`$smwgBrowseByApi`](https://www.semantic-mediawiki.org/wiki/Help:$smwgBrowseByApi) to `false`) 
* #1761 Added content language context to recognize localized property type `[[Has type ...]]` annotations
* #1764 Added `--with-maintenance-log` option to the "rebuildFulltextSearchTable.php" maintenance script
* #1768 Improved general error display to be more user context friendly
* #1779 Added [`Special:ProcessingErrorList`](https://www.semantic-mediawiki.org/wiki/Help:Special:ProcessingErrorList) 
* #1793 Extended `#LOCL` support for the date type (`TimeValue`)

## Bug fixes

* #1328 Fixed a "Undefined index: language" error in `#smwdoc` parser function
* #1713 Fixed a "Segmentation fault" when `QueryResultDependencyListResolver` tries to resolve a category/property hierarchy with a circular reference
* #1715 Fixed decoding of a single quotation mark in `DisplayTitlePropertyAnnotator`
* #1724 Fixed a possible `InvalidArgumentException` in connection with `SMW_DV_PVUC` by updating the `CachedPropertyValuesPrefetcher` version number
* #1727 Fixed an issue when property names contain `<` or `>` symbols 
* #1728 Fixed fatal error in `Special:SearchByProperty` on when the property name contains invalid characters
* #1731 Fixed possible error in the `SkinAfterContent` hook when a null object is used
* #1775 Fixed time offset recognition 

## Internal changes

* #1511 Removed I18n shim originally required for MediaWiki < 1.23
* #1726 Allows `QueryDependencyLinksStore` to execute `getDependencyListByLateRetrieval` even in cases of an intial empty list
* #1750 Added `RdbmsTableBuilder` to replace `SMWSQLHelpers`
* #1780 Added `ResourceBuilder` and `DispatchingResourceBuilder`
* #1791 Added `PropertyRegistry::registerPropertyDescriptionByMsgKey`

## Contributors

* ...
