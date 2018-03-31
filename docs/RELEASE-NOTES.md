# Semantic MediaWiki 3.0

This is not a release yet and is planned to be available in Q2 2018.


## Highlights

Highlights for this release include ... (#2065)


### Visual changes

#2893, #2898 (`Special:Ask`), #2891, #2875, (`Special:Browse`), and #2906 (Factbox) contains changes to the appearance to help improve responsiveness on small screens and/or distinguish visual components more clearly from each other.

## Upgrading

This release requires to run the `setupStore.php` or `update.php` script. (#2065, #2461, #2499)

After the upgrade, please check the "Deprecation notices" section in `Special:SemanticMediaWiki` to adapt and modify listed deprecated settings.

If you are still using maintenance scripts identifiable by the `SMW_` prefix you must now migrate to the new maintenance spript names. See the help pages on [maintenance scrips](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_scripts) for further information.

**Please also carefully read the section on breaking changes and deprecations further down in these release notes. We have also prepared a [migration guide](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_3.0.0/Migration_guide) for you.**


## New features and enhancements

* [#794](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/794) Added `SMW_PARSER_UNSTRIP` to [$smwgParserFeatures](https://www.semantic-mediawiki.org/wiki/Help:$smwgParserFeatures) enabling to use unstripped content on a text annotation
* [#2065](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2065) Added entity specific collation support with help of the [`$smwgEntityCollation`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEntityCollation) setting
* [#2348](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2348) Allow showing annotations even if they are improper for datatype "Text"
* [#2398](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2398) Added `#ask` and `#show` parser function support for `@deferred` output mode
* [#2420](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2420) Added support for a datatable output in the `format=table` (and `broadtable`) result printer
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
* [#2632](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2632) Added [uniqueness violation](https://www.semantic-mediawiki.org/wiki/Help:Property_uniqueness) check on the property page for the property label used
* [#2662](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2662) Added `+depth` as syntax component for a condition to restrict the depth of class and property hierarchy queries
* [#2673](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2673)
* [#2677](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2677) Added `+width` as parameter to the `format=table` (and `broadtable`) result printer
* [#2690](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2690) Added the `type` parameter to `format=json` in support for a simple list export
* [#2696](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2696) Added a new `smwbrowse` API module ([#2717](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2717), [#2719](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2719), [#2721](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2721))
* [#2699](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2699) Added an [input assistance](https://www.semantic-mediawiki.org/wiki/Help:Input_assistance) for the `Special:Ask` condition textbox
* [#2718](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2718) Added ad-hoc export for the `format=table` datatable
* [#2726](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2726) Added entity [input assistance](https://www.semantic-mediawiki.org/wiki/Help:Input_assistance) for wikiEditor and the `Special:Search` input field ([#2756](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2756))
* [#2738](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2738) Added information whether `SMWSearch` search mode is enabled or not for `Special:Search`
* [#2776](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2776) Added tracking of changes to categories (see 2495)
* [#2785](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2785) Added new styling to property page value list
* [#2796](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2796) Allows "rendering of HTML" on special page "Ask" when using `|headers=plain` in queries
* [#2801](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2801) Added `--skip-optimize` and `--skip-import` to `setupStore.php` (see 2516)
* [#2803](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2803) Filter categories from transcluded content in `format=embedded`
* [#2815](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2815) Added `#nowiki` support for external identifier type
* [#2820](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2820) Added check on declarative property usage
* [#2822](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2822) Added add `merge` parameter to `format=csv`
* [#2824](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2824) Added `bom` as parameter to `format=csv`
* [#2826](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2826) Added `valuesep` as parameter to `format=csv` to define a value separator
* [#2840](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2840) Added [`$smwgPropertyReservedNameList`](https://www.semantic-mediawiki.org/wiki/Help:$smwgPropertyReservedNameList) to define reserved property names
* [#2842](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2842) Added [`$smwgURITypeSchemeList`]((https://www.semantic-mediawiki.org/wiki/Help:$smwgURITypeSchemeList)) to restrict valid URI scheme
* [#2844](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2844) Rename output formatter `#-ia` to `#-raw`
* [#2861](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2861) Added restriction for a property name that contains a CR, LF
* [#2867](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2867) Added singular, plural category canonical check
* [#2873](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2873) Added support for `in:` as expression to the #ask syntax
* [#2874](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2874) Added property group support in `Special:Browse`
* [#2875](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2875) Changed the `Special:Browse` theme to `smwb-theme-light`
* [#2878](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2878) Added value filter to the property page
* [#2882](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2882) Added detection of duplicate entities upon storage
* [#2883](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2883) Added display of duplicate entities to `Special:Admin`
* [#2889](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2889) Added method to make subobject sortkeys distinguishable
* [#2891](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2891) Added flex (responsive) mode to `Special:Ask` and `Special:Browse` div table
* [#2893](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2893) Changed `Special:Ask` appearance ([#2898](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2898))
* [#2895](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2895) Changed display of named subobject caption to appear without an underscore
* [#2906](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2906) Added flex (responsive) mode to the factbox
* [#2907](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2907) Added modal help to `Special:Ask`
* [#2913](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2913) Added a job queue watchlist feature and the [`$smwgJobQueueWatchlist`](https://www.semantic-mediawiki.org/wiki/Help:$smwgJobQueueWatchlist) setting
* [#2916](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2916) Added supplements jobs during the installation process
* [#2922](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2922) Added `SMW_BROWSE_SHOW_SORTKEY` flag to the `$smwgBrowseFeatures` setting
* [#2930](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2930) Added limit to value selection on the property page
* [#2932](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2932) Added `removeDuplicateEntities.php` script to remove duplicate entities
* [#2933](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2933) Added [`$smwgDefaultLoggerRole`](https://www.semantic-mediawiki.org/wiki/Help:$smwgDefaultLoggerRole) setting to define logging granularity for Semantic MediaWiki
* [#2953](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2953) Added support for natural sort (`n-asc`, `n-desc`) of printout column values
* [#2973](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2973)
* [#3006](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3006) Disabled autocomplete on `[[...` in Special:Search for `SMWSearch` type
* [#3009](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3009) Added `#tick` and `#num` output formatter to boolean value type
* [#3011](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3011) Added the [`$smwgDefaultOutputFormatters`](https://www.semantic-mediawiki.org/wiki/Help:$smwgDefaultOutputFormatters) setting to declare default output formatter for a type or property
* [#3017](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3017) Added the [`$smwgCompactLinkSupport`](https://www.semantic-mediawiki.org/wiki/Help:$smwgCompactLinkSupport) setting to compact links produced by `Special:Ask` and `Special:Browse`
* [#3019](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3019) Added experimental support for the `SMW_NS_RULE` namespace
* [#3020](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3020) Added the keyword (`_keyw`) type
* [#3024](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3024) Added `format=templatefile` to support individual export formats defined using MediaWiki templates
* [#3029](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3029) Added function to keep updated entities in-memory to improve rebuild performance
* [#3052](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3052)
* [#3080](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3080)
* [#3088](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3088)

## Bug fixes

* [#839](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/839) Fixed and extended `Special:Ask` to be more maintainable
* [#2505](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2505) Fixed hard-coded default value for `format=csv`
* [#2586](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2586) Fixed class assignments for empty cells in `format=table`
* [#2621](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2621) Fixed sort/order field behaviour in `Special:Ask`
* [#2652](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2652) Fixed handling of multiple checkbox parameter in `Special:Ask`
* [#2767](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2767)
* [#2773](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2773)
* [#2817](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2817)
* [#2881](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2881)
* [#2884](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2884)
* [#2896](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2896)
* [#2902](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2902)
* [#2909](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2909)
* [#2915](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2915)
* [#2917](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2917)
* [#2919](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2919) Fixed fetching all entities during a delete
* [#2958](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2958)
* [#2963](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2963)
* [#2986](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2986)
* [#3000](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3000)
* [#3010](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3010)
* [#3025](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3025) Fixed storage of query information during a `preview` activity
* [#3026](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3026) Fixed replacement of `smw_proptable_hash` during setup
* [#3031](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3031) Fixed duplicate entry `smw_prop_stats` exception
* [#3033](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3033) Fixed "The supplied ParserOptions are not safe ... set $forceParse = true" during `Special:Upload`
* [#3049](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3049)
* [#3067](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3067)
* [#3076](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3076)

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
* [#2841](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2841) Replaced `$smwgLinksInValues` with the `SMW_PARSER_LINV` flag now maintained in `$smwgParserFeatures`, PCRE option has been removed
* [#2880](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2880)
* [#2899](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2899)
* [#2927](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2927)
* [#2944](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2944)
* [#2961](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2961)
* [#2995](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2995)

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
* [#2845](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2845)
* [#2847](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2847)
* [#2888](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2888)
* [#2908](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2908)
* [#2928](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2928)
* [#2969](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2969)
* [#2972](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2972)
* [#3032](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3032) Added the `SMW::LinksUpdate::ApprovedUpdate` and `SMW::Parser::ChangeRevision` hook
* [#3061](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3061)
* [#3063](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3063)
* [#3070](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3070)
* [#3082](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3082)


## Contributors

...
