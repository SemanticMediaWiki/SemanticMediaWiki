# Semantic MediaWiki 2.5.2

Released on May 17, 2017.

## Enhancements

* [#2449](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2449) as `8783268` Made property pages show the source name of the redirect (synonym) without a `DisplayTitle` formatter
* Many new translations for numerous languages by the communtity of [translatewiki.net](https://translatewiki.net/w/i.php?title=Special%3AMessageGroupStats&x=D&group=mwgithub-semanticmediawiki&suppressempty=1)

## Bug fixes and internal code changes

* [#2413](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2413) as `313d08e` Enforced `NO_DEPENDENCY_TRACE` on queries with namespace `NS_SPECIAL`
* [#2426](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2426) as `595efea` Removed duplicate entry for `$smwgFulltextSearchPropertyExemptionList`
* [#2434](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2434) as `bb6ef9a` Made `ParserAfterTidy` to check "readOnly" mode
* [#2438](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2438) as `ba2c6e7` Made `ArticlePurge` add a safeguard to flush query result cache
* [#2444](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2444) as `8c9c4c3` Fixed `NamespaceManager` to avoid reset of user settings
* [#2446](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2446) as `6697da4` Added safeguard against duplicate ID creation
* [#2448](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2448) as `60cd466` Added usage of `forcedUpdate` on redirect jobs
* [#2450](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2450) as `4adfd2d` Fixed `QueryDependencyLinksStore` to avoid `ORDER BY/GROUP BY` on select
* [#2451](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2451) as `8a9bef2` Fixed "ext.smw.dataItem.time.js" to construct a UTC date object
* [#2457](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2457) as `5619b55` Fixed `JulianDay` values to use a consistent format
* [#2463](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2457) as `3f7f47e` Made `SMWSql3SmwIds` set legacy cache only on success
