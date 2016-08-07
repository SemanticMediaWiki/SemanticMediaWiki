# Semantic MediaWiki 2.5

THIS IS NOT A RELEASE YET

## Highlights

*

## Compatibility changes

*

## New features and enhancements

* #1759 Improveed `Special:Ask` error output 

## Bug fixes

* #1328 Fixed a "Undefined index: language" error in `#smwdoc` parser function
* #1713 Fixed a "Segmentation fault" when `QueryResultDependencyListResolver` tries to resolve a category/property hierarchy with a circular reference
* #1715 Fixed decoding of a single quotation mark in `DisplayTitlePropertyAnnotator`
* #1724 Fixed a possible `InvalidArgumentException` in connection with `SMW_DV_PVUC` by updating the `CachedPropertyValuesPrefetcher` version number
* #1727 Fixed an issue when property names contain `<` or `>` symbols 
* #1728 Fixed fatal error in `Special:SearchByProperty` on when the property name contains invalid characters
* #1731 Fixed possible error in the `SkinAfterContent` hook when a null object is used

## Internal changes

* #1511 Removed I18n shim originally required for MediaWiki < 1.23
* #1726 Allows `QueryDependencyLinksStore` to execute `getDependencyListByLateRetrieval` even in cases of an intial empty list
* #1750 Added `RdbmsTableBuilder` to replace `SMWSQLHelpers`

## Contributors

*
