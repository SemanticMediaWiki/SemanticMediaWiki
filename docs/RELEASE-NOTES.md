# Semantic MediaWiki 3.0

This is not a release yet and is planned to be available in Q1 2018.


## Highlights

Highlights for this release include ... (#2065)


## Upgrading

This release requires to run the `setupStore.php` or `update.php` script. (#2065, #2461, #2499)

After the upgrade, please check the "Deprecation notices" section in `Special:SemanticMediaWiki` to adapt and modify listed deprecated settings.

If you are still using maintenance scripts identifiable by the `SMW_` prefix you must now migrate to the new maintenance spript names. See the help pages on [maintenance scrips](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_scripts) for further information.

**Please also carefully read the section on breaking changes and deprecations further down in these release notes. We have also prepared a [migration guide](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_3.0.0/Migration_guide) for you.**


## New features and enhancements

* [#794](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/794)
* [#2065](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2065) Added entity specific collation support with help of the [`$smwgEntityCollation`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEntityCollation) setting
* [#2348](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2348) Allow showing annotations even if they are improper for datatype "Text"
* [#2398](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2398) Added `#ask` and `#show` parser function support for `@deferred` output mode
* [#2420](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2420) Added support for a datatable output in the `format=table` (and `broadtable`) result printer
* [#2432](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2432) Added check for MediaWiki's `readOnly` mode
* [#2435](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2435) Added filtering of invisible characters (non-printable, shyness etc.) to armor against incorrect annotations
* [#2453](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2453) Changed the approach on how referenced properties during an article delete are generated to optimize the update dispatcher
* [#2461](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2461) Improved performance on fetching incoming properties
* [#2471](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2471) Added [`$smwgUseCategoryRedirect`](https://www.semantic-mediawiki.org/wiki/Help:$smwgUseCategoryRedirect) setting to allow finding redirects on categories
* [#2476](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2476) Added [`$smwgQExpensiveThreshold`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQExpensiveThreshold) and [`$smwgQExpensiveExecutionLimit`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQExpensiveExecutionLimit) to count and restrict expensive `#ask` and `#show` functions on a per page basis
* [#2494](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2494) Added [`$smwgChangePropagationProtection`](https://www.semantic-mediawiki.org/wiki/Help:$smwgChangePropagationProtection) and changed the approach on how property modifications are propagated
* [#2499](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2499) Added [`$smwgFieldTypeFeatures`](https://www.semantic-mediawiki.org/wiki/Help:$smwgFieldTypeFeatures) with `SMW_FIELDT_CHAR_NOCASE` to enable case insensitive search queries
* [#2515](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2515) Added support for `#LOCL#TO` date formatting to display a [local time](https://www.semantic-mediawiki.org/wiki/Local_time) offset according to a user preferrence
* [#2516](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2516) Added an optimization run during the installation process (`setupStore.php`) for tables managed by Semantic MediaWiki
* [#2536](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2536) Added `SMW_FIELDT_CHAR_LONG` as flag for  `$smwgFieldTypeFeatures` to extend the indexable length of blob and uri fields to max of 300 chars
* [#2543](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2543) Extended [`EditPageHelp`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEnabledEditPageHelp) to be disabled using a user preference
* [#2558](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2558) Added `like:` and `nlike:` comparator operator for approximate queries
* [#2561](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2561) Added listing of improper assignments to the property page for an easier visual control
* [#2572](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2572) Added `@annotation` as special processing mode to embedded `#ask` queries
* [#2595](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2595) Improved the content navigation in `Special:SemanticMediaWiki`
* [#2600](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2600) Added `$smwgCreateProtectionRight` setting to control the creation of new properties and hereby annotations as part of the [authority mode](https://www.semantic-mediawiki.org/wiki/Authority_mode)
* [#2615](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2615) Added `filter=unapprove` to `Special:WantedProperties`
* [#2632](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2632) Added [uniqueness violation](https://www.semantic-mediawiki.org/wiki/Help:Property_uniqueness) for a property label to be displayed on the property page
* [#2662](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2662) Added `+depth` as syntax component for a condition to restrict the depth of class and property hierarchy queries
* [#2673](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2673)
* [#2677](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2677) Added `+width` as parameter to the `format=table` (and `broadtable`) result printer
* [#2690](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2690) Added the `type` parameter to `format=json` in support for a simple list export
* [#2696](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2696) Added a new `smwbrowse` API module
* [#2699](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2699) Added an input assistance for the `Special:Ask` condition textbox
* [#2717](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2717) Added addtional index to bettter serve the `smwbrowse` API module
* [#2718](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2718) Added ad-hoc export for the `format=table` datatable
* [#2719](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2719) 
* [#2721](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2721)
* [#2726](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2726)
* [#2738](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2738)
* [#2756](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2756)
* [#2776](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2776)
* [#2785](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2785)
* [#2796](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2796) Allows "rendering of HTML" on special page "Ask" when using `|headers=plain` in queries
* [#2801](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2801)
* [#2803](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2803)
* [#2805](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2805)
* [#2815](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2815)
* [#2820](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2820)
* [#2822](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2822)
* [#2824](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2824)
* [#2826](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2826)

## Bug fixes

* [#839](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/839) Fixed and extended `Special:Ask` to be more maintainable
* [#2586](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2586) Fixed class assignments for empty cells in `format=table`
* [#2621](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2621) Fixed sort/order field behaviour in `Special:Ask`
* [#2652](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2652) Fixed handling of multiple checkbox parameter in `Special:Ask`
* [#2767](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2767)
* [#2773](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2773)
* [#2817](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2817)


## Breaking changes and deprecations

* [#2495](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2495) `Store::getPropertySubjects` and `Store::getAllPropertySubjects` will return an `Iterator` instead of just an array
* [#2588](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2588) Removed special page "SemanticStatistics"
* [#2611](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2611) Removed the user preference `smw-ask-otheroptions-collapsed-info`
* [#2640](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2640) Removed `$smwgAutocompleteInSpecialAsk`
* [#2659](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2659) Removed deprecated constant `SMWDataItem::TYPE_STRING` (replaced by `SMWDataItem::TYPE_BLOB`)
* [#2696](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2696) Soft deprecate the `browsebyproperty` API module, the new `smwbrowse` should be used instead
* [#2705](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2705) Removed usages of deprecated `ResultPrinter::getParameters`
* [#2724](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2724)
* [#2730](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2730)
* [#2732](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2732)
* [#2748](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2748)
* [#2750](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2750)
* [#2752](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2752)
* [#2761](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2761)
* [#2768](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2768)
* [#2788](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2788) Resources are now being exported as Internationalized Resource Identifiers (IRI) by default.
* [#2790](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2790) Removed deprecated entry points for maintenance scripts
* [#2802](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2802)
* [#2806](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2806)
* [#2821](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2821)

## Other changes

* [#2485](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2485) Disabled updates by the `QueryDependencyLinksStore` on a 'stashedit' activity
* [#2491](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2491) Added `ChunkedIterator` to `DataRebuilder` to avoid OOM situations in case of a large update queue
* [#2535](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2535) Fixed property namespace (`_wpp`) display in `WikiPageValue`
* [#2540](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2540) Added type `parser-html` to [`JSONScript`](https://www.semantic-mediawiki.org/wiki/Help:Integration_tests) testing to allow assertions on HTML structure
* [#2591](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2591) Discontinued reading MediaWiki `job` table, use the `JobQueue::getQueueSizes` instead
* [#2609](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2609) Added check to `Special:Ask` to require JavaScript
* [#2631](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2631) Disabled purge button while JS resources are still loaded
* [#2650](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2650) Replaced some styles in `Special:Ask`
* [#2653](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2653) Fixed `broadtable` width on MobileFrontend
* [#2676](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2676) Added support for column default values in the `TableBuilder`
* [#2680](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2680) Added `null_count` column to `PropertyStatisticsTable`
* [#2691](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2691) Replaced `#info` icon set
* [#2698](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2698) Added persistent caching to the `HierarchyLookup`
* [#2714](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2714) Added `SMW::GetPreferences` hook
* [#2727](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2727)
* [#2745](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2745)
* [#2747](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2747)
* [#2751](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2751)
* [#2765](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2765)
* [#2774](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2774)
* [#2783](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2783)
* [#2785](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2785)


## Contributors

...
