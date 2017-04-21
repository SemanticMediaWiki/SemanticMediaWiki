# Semantic MediaWiki 2.5.1

Released on April 22, 2017.

## New feature

* `ec3810d` Added "DeprecationNoticeTaskHandler" providing [depreciation notices](https://www.semantic-mediawiki.org/wiki/Help:Special:SemanticMediaWiki/Deprecation_notices) to system admins (#2357, #2384, #2401)

## Enhancements

* `c781b02` Added `Job::lazyPush`, and `SMW_HTTP_DEFERRED_LAZY_JOB` to [`smwgEnabledHttpDeferredJobRequest`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEnabledHttpDeferredJobRequest) (#2356)
* `769ca88` Removed foreign "Property", "Concept" named canonical namespaces (#2358)
* `ec6d5c1` Made "AllowsListValue" add more verbose error message (#2367)
* `cd31a79` Extended contents importer to support importing MediaWiki's XML format ("XmlContentCreator") (#2386)
* `6d11e5a` Improved mobile display for special page "Browse" by replaceing "`<table>`" with "`<div>`" (#2387)
* `74afabe` Ensured the content for the full-text search is in sync with the "SemanticData" primary data update (#2388)
* `3e32ad3` Enabled the display of query references on the subobject level (#2414)
* `55b3d99` Made "WikiPageValue" add an error message with property hint (#2417)

## Notices

* `4c004e4` Deprecated [`$smwgAdminRefreshStore`](https://www.semantic-mediawiki.org/wiki/Help:$smwgAdminRefreshStore) in favor of
[`$smwgAdminFeatures`](https://www.semantic-mediawiki.org/wiki/Help:$smwgAdminFeatures) to be removed with SMW 3.1.0 (#2362)
* `aba22d8` Fixed inconsistent list name parameter settings (#2364):  
  * [`$smwgQueryDependencyPropertyExemptionList`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQueryDependencyPropertyExemptionList)
instead of now deprecated `$smwgQueryDependencyPropertyExemptionlist` to be removed with SMW 3.1.0 and  
  * [`$smwgQueryDependencyAffiliatePropertyDetectionList`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQueryDependencyAffiliatePropertyDetectionList)
instead of now deprecated `$smwgQueryDependencyAffiliatePropertyDetectionlist` to be removed with SMW 3.1.0

## Bug fixes and internal code changes

* `8a9b94d` Removed the `[` encoding introduced in #1768 to allow some post-processing to detect the necessary links (#2351)
* `bc6e74d` Made "PropertyLabelFinder" set Query::PROC_CONTEXT
* `401b052` Made `Store::updateData` add timer point
* `2414cb8` Made "URIResolver" avoid "Undefined index: HTTP_ACCEPT" (#2353)
* `21ee86c` Made "PropertyStatisticsTable" avoid "Out of range value ..." in DB strict mode (#2354)
* `ed5686a` Fixed "SubSemanticData::copyDataFrom ... null given" (#2359)
* `85b2386` Made "IdTaskHandler" to use an int value (EntityIdDisposerJob::dispose) (#2361)
* `bad1460` Made "Highlighter" to pre-process title content (#2363)
* `acebf0b` Made "JsonTestCaseFileHandler" to check and skip on .x release (#2366)
* `f5a30dd` Made persistent update marker for MW 1.29+ to be tracked (#2365)
* `8a37d42` Made "InternalParseBeforeLinks" to detect `SMW off/on` (#2373)
* `6ddb4c6` Added `PropertyLabelSimilarityLookup::getPropertyMaxCount` (#2374)
* `5d51d2c` Made special page "Browse" avoid "Uncaught Error: Unknown dependency: jquery.ui.autocomplete" by the MobileFrontend extension (#2377)
* `85bd78c` Set `Message::setInterfaceMessageFlag` (#2378)
* `727b825` Made "ErrorMsgTextValue" display unparsed text in short wiki mode (#2385)
* `b1800eb` Made "ParserCachePurgeJob" count links from the source (#2389)
* `4444437` Made tests for 1.23 skip on unknown "ImportSource" interface (#2394)
* `013da5a` Made "PageUpdater::isHtmlCacheUpdate", "DeferrableUpdate" work with MW 1.29+ (#2393)
* `1bec002` Introduced PHPUnit 5.7 required changes (#2395)
* `2af43cb` Made `SemanticData::getPropertyValues` to always return an indexed array (#2397)
* `d757a3c` Changed to use `interface_exists` instead of `class_exists` (#2399)
* `982f1dc` Normalized error message in API output (#2405)
* `7d4a0f5` Distinguished `prop.chain` sort on duplicate detection (#2406)
* `f994fd2` Fixed tests due to changes on selflinks "`<a>`" tags vs. "`<strong>`" tags (#2407)
* `bfdd2f1` Added `HtmlTable` (#2409)
* `d2cb5b7` Made the index parameter on `+|lang` to be avoided (#2410)
* `d6aca45` Made special page "Ask" to produce correct printout position for `+|` parameter (#2412)
* `d017a15` Made "UniquenessConstraintValueValidator" to enforce `NO_DEPENDENCY_TRACE` (#2413)
* `88e8884` Made "ExternalFormatterUriValue" avoid already encoded values (#2415)
* `c1d96dd` Tidied "Importer" (#2418)
* `ca9cfaf` Tidied "InMemoryEntityProcessList" (#2419)
* `c08fd82` Introcuded better approach to solve the printout position (#2421)
