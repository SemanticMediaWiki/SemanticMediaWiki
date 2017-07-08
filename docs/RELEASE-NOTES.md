# Semantic MediaWiki 2.5.3

Released on July 8, 2017.

## Enhancements

* [#2534](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2534) as `d7077b8` Added [`$smwgLocalConnectionConf`](https://www.semantic-mediawiki.org/wiki/Help:$smwgLocalConnectionConf) configuration parameter together with respective functionality allowing for modifications on connection providers in environments with multiple relational databases
* Many new translations for numerous languages by the communtity of [translatewiki.net](https://translatewiki.net/w/i.php?title=Special%3AMessageGroupStats&x=D&group=mwgithub-semanticmediawiki&suppressempty=1)

## Bug fixes and internal code changes

* [#2379](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2379) as `7c98b4a` Removed `ContentParser::forceToUseParser` from tests
* [#2459](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2459) as `a7b3f00` Switched Travis CI integration test to use Ubuntu Trusty operating system environment
* [#2460](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2460) as `3b6e30d` Made `ArticleDelete` restrict the pool of properties in update dispatcher
* [#2472](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2472) as `03e0b8c` Added debug output to Travis CI integration tests
* [#2473](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2473) as `9f78042` Replaced `isSupportedLanguage` with `isKnownLanguageTag` to allow for any known language usage
* [#2474](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2474) as `d1ba666` Fixed limit when the number of results is greater as the `$smwgQMaxLimit` or in `$smwgQMaxInlineLimit` where it is reset to the default value despite the global limitation
* [#2475](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2475) as `a3499b6` Fixed behavior in case of `$wgCapitalLinks = false;` by restricting property name uppercase conversion to special properties only
* [#2477](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2477) as `c12fec7` Fixed `UpdateDispatcherJob` to check for null title
* [#2478](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2478) as `681b0fc` Tidyed `QueryToken`
* [#2481](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2481) as `7c3900f` Made `RequestOptions` cast "int" on `limit` and `offset`
* [#2482](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2482) as `2ff92bd` Added TransactionalDeferredCallableUpdate
* [#2491](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2491) as `ca36069` Provided `ChunkedIterator` to avoid possible out of memory situations in cases where outdated entities reach a unhandable level
* [#2493](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2493) as `409025d` Prevended unintended override of `PropertyTablePrefix` in hook
* [#2496](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2496) as `fb3d604` Normalized message value arguments
* [#2500](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2500) as `3edb303` Made "Special:Browse" avoid API request on legacy setting
* [#2502](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2502) as `a527bbe` Provided POST purge link to avoid confirmation by users using action "purge"
* [#2512](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2512) as `86f9733` Made `DataRebuilder` to report progress on disposed entities
* [#2518](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2518) as `a851f8d` Prevended "PHP Notice: A non well formed numeric value encountered" on `Title::touched`
* [#2522](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2522) as `36cec82` Set a comma as default for `valuesep` with the "template" format
* [#2524](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2524) as `36cec82` Ensured that only marked `isDeferrableUpdate` can use a `transactionTicket`
* [#2526](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2526) as `9d3e0f2` Prevented failing test in `QueryDependencyLinksStoreTest`
* [#2527](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2527) as `f72df04` Made `BooleanValue` always recognize canonical boolean string
* [#2530](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2530) as `ad32a26` Made `InternalParseBeforeLinks` cast `$smwgEnabledSpecialPage` setting late
* `2bf07c3` Removed update marker on delete event
