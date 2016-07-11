# Semantic MediaWiki 2.5

THIS IS NOT A RELEASE YET

## Highlights

*

## Compatibility changes

*

## New features and enhancements

*

## Bug fixes

* #1328 Fixed a "Undefined index: language" error in `#smwdoc` parser function
* #1713 Fixed a "Segmentation fault" when `QueryResultDependencyListResolver` tries to resolve a category/property hierarchy with a circular reference
* #1715 Fixed decoding of a single quotation mark in `DisplayTitlePropertyAnnotator`
* #1724 Fixed a possible `InvalidArgumentException0` in connection with `SMW_DV_PVUC` by updating the `CachedPropertyValuesPrefetcher` version number

## Internal changes

* #1726 Allows `QueryDependencyLinksStore` to execute `getDependencyListByLateRetrieval` even in cases of an intial empty list

## Contributors

*
