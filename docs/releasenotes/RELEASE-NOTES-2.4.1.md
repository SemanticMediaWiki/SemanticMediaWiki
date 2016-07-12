# Semantic MediaWiki 2.4.1

Released on July 12th, 2016.

## Bug fixes

* #1328 Fixed a "Undefined index: language" error in `#smwdoc` parser function
* #1713 Fixed a "Segmentation fault" when `QueryResultDependencyListResolver` tries to resolve a category/property hierarchy with a circular reference
* #1715 Fixed decoding of a single quotation mark in `DisplayTitlePropertyAnnotator`
* #1724 Fixed a possible `InvalidArgumentException` in connection with `SMW_DV_PVUC` by updating the `CachedPropertyValuesPrefetcher` version number
* #1726 Allows `QueryDependencyLinksStore` to execute `getDependencyListByLateRetrieval` even in cases of an intial empty list
* #1727 Fixed an issue when property names contain `<` or `>` symbols
* #1728 Fixed fatal error in `Special:SearchByProperty` on when the property name contains invalid characters
* #1731 Fixed possible error in the `SkinAfterContent` hook when a null object is used
