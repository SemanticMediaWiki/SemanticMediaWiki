# Semantic MediaWiki 2.4.5

Released on January 16th, 2017.

## Bug fixes

* <code>e3689e6</code> Fixed datatypes not being recognized on property pages
* #2124 Fixed to use `wfCgiToArray` to avoid deprecation notice for `SMWInfolink`
* #2156 Fixed Javascript error caused by `wikiScript` being undefined
* #2160 Fixed `ParserCachePurgeJob` to be avoided on an empty request
* #2166 Fixed `QueryDependencyLinksStore` to check for a null title
