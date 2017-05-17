# Semantic MediaWiki 2.5.1

Released on April 22, 2017.

## New feature

* [#2357](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2357) as `ec3810d` Added [deprecation notices](https://www.semantic-mediawiki.org/wiki/Help:Special:SemanticMediaWiki/Deprecation_notices) system (#2357, #2384, #2401) to `Special:SemanticMediaWiki` in support for the upcoming 3.0 release

## Enhancements

* [#2356](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2356) as `c781b02` Extended [`smwgEnabledHttpDeferredJobRequest`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEnabledHttpDeferredJobRequest) to allows `SMW_HTTP_DEFERRED_LAZY_JOB` (#2356)
* [#2358](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2358) as `769ca88` Enforces "Property" and "Concept" canonical namespaces
* [#2367](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2367) as `ec6d5c1` Added a more verbose error message for failed [allows values](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Allows_value_list)
* [#2386](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2386) as `cd31a79` Extended the [contents importer](https://www.semantic-mediawiki.org/wiki/Help:Contents_importer) to support the MediaWiki's XML format
* [#2387](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2387) as `6d11e5a` Improved the display of `Special:Browse` in connection with mobile devices and the `MobileFrontend` extension
* [#2388](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2388) as `74afabe` Ensured the content for the full-text search is in sync with the "SemanticData" primary data update
* [#2414](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2414) as `3e32ad3` Add support for the display of [query references](https://www.semantic-mediawiki.org/wiki/Help:Query_reference) on a subobject
* [#2417](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2417) as `55b3d99` Add a more verbose error message to the "WikiPageValue"

## Bug fixes and internal code changes

* [#2351](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2351) as `8a9b94d` Fixed `[` encoding in `Highlighter` to allows for some `#info` post-processing
* [#2353](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2353) as `2414cb8` Fixed "Undefined index: HTTP_ACCEPT" in Special:URIResolver
* [#2354](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2354) as `21ee86c` Fixed a "Out of range value ..." in DB strict mode caused by the "PropertyStatisticsTable"
* [#2359](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2359) as `ed5686a` Fixed a "SubSemanticData::copyDataFrom ... null given" message
* [#2361](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2361) as `85b2386` Fixed `EntityIdDisposerJob::dispose` to use an int value
* [#2363](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2363) as `bad1460` Fixed pre-process of title content in the `Highlighter`
* [#2365](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2365) as `f5a30dd` Added update marker to track and avoid having `refreshLinksPrioritized` (MW 1.29+) to issue store updates
* [#2373](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2373) as `8a37d42` Added detection of `SMW off/on` for annotations within system messages
* [#2374](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2374) as `6ddb4c6` Added detection of property max count to `Special:PropertyLabelSimilarity`
* [#2377](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2377) as `5d51d2c` Fixed "Uncaught Error: Unknown dependency: jquery.ui.autocomplete" in `Special:Browse` when displayed by the `MobileFrontend` extension
* [#2385](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2385) as `727b825` Fixed display if unparsed error text in wikitext display
* [#2389](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2389) as `b1800eb` Fixed counting of links in `ParserCachePurgeJob`
* [#2393](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2393) as `013da5a` Added `PageUpdater::isHtmlCacheUpdate` to disable `HTMLCacheUpdateJob ` due to [T154427](https://phabricator.wikimedia.org/T154427)
* [#2397](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2397) as `2af43cb` Fixed `SemanticData::getPropertyValues` to always return an indexed array
* [#2405](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2405) as `982f1dc` Fixed normalization of error messages in the `API` output
* [#2406](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2406) as `7d4a0f5` Fixed duplicate detection for sort conditions in `prop.chain` notations in connection with [`$smwgQFilterDuplicates`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQFilterDuplicates)
* [#2410](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2410) as `d2cb5b7` Fixed the appearance of an `index` parameter in the `further results` link in connection with the `+|lang` printout parameter
* [#2412](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2412) as `d6aca45` Fixed order of parameters in `Special:Ask` on the event of a `further results` link that contains `+|...` parameters
* [#2413](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2413) as `d017a15` Fixed ID creation of temporary queries in `UniquenessConstraintValueValidator` when a uniqueness constraint isn't cached
* [#2415](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2415) as `88e8884` Fixed URI value encoding for the [`External identifier`](https://www.semantic-mediawiki.org/wiki/Help:Type_External_identifier) type

## Deprecations

* [#2362](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2362) as `4c004e4` Deprecated [`$smwgAdminRefreshStore`](https://www.semantic-mediawiki.org/wiki/Help:$smwgAdminRefreshStore) in favor of
[`$smwgAdminFeatures`](https://www.semantic-mediawiki.org/wiki/Help:$smwgAdminFeatures) to be removed with SMW 3.1.0
* [#2364](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2364) as `aba22d8` Fixed inconsistent list name parameter settings :
  * [`$smwgQueryDependencyPropertyExemptionList`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQueryDependencyPropertyExemptionList)
instead of now deprecated `$smwgQueryDependencyPropertyExemptionlist` to be removed with SMW 3.1.0 and
  * [`$smwgQueryDependencyAffiliatePropertyDetectionList`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQueryDependencyAffiliatePropertyDetectionList)
instead of now deprecated `$smwgQueryDependencyAffiliatePropertyDetectionlist` to be removed with SMW 3.1.0
