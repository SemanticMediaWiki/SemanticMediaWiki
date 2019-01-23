# Semantic MediaWiki 3.0

Released on October 11, 2018.

## Highlights

This release brings many highlights:

### User interface changes

Several user interface changes are deployed to make user facing front-end components more intutive and mobile-friendly by improving the responsiveness on small screens including:

* Special page "Ask" ([#2891](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2891), [#2893](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2893), [#2898](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2898), [#3415](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3415)) – including further enhancements, most notably input assistance on input fields ([#2699](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2699)), comprehensive input help ([#2907](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2907)) and compact links ([#3017](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3017))
* Special page "Browse" ([#2891](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2891), [#2875](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2875)) – including further enhancements, grouping of properties ([#2874](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2874)) and compact links ([#3017](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3017))
* Special page "SemanticMediaWiki" ([#3218](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3218))
* Property pages – boxed pagination ([#3236](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3236)), tabbed navigation ([#3308](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3308)) including usage count information ([#3440](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3440)) and custom tabs ([#3416](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3416))
* Concept pages – boxed pagination ([#3236](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3236)), tabbed navigation ([#3308](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3308)) and custom tabs ([#3416](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3416))
* Factbox ([#2906](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2906))
* Special page "Concepts" ([#3333](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3333))

### List formats and template format rework

The "list" formats (`list`, `ol` and `ul`) and the `template` format were completely reworked with the latter being renamed to `plainlist` [(#3130)](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3130) now being the default result format if no result format was explicitly specifed for the query. Most notably dedicated separators for values, properties and result "rows" (`sep`, `propsep`, `valuesep`) were introduced as well as class attributes to HTML elements of "list", "ol" and "ul" formats were added to facilitate easy indidual styling. Note that the `plainlist` format does not apply these additional class attributes.

**See the [migration guide](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/migration-guide-3.0.md#list-formats-incl-list-ol-ul-template) for a comprehensive overview of the changes done.**

### Search and query

Local-specific (ICU) sorting and collation is now possible for pages as well as values of datatype "Page" [(#2065)](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2065) facilitated via configuration parameter [`$smwgEntityCollation`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEntityCollation) [(#2429).](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2429)

Special page "Search" now provides and additional search form accessible via the "Extended" selector in case the ["SMWSearch" feature](https://www.semantic-mediawiki.org/wiki/Help:SMWSearch) was enabled [(#3126).](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3126) with custom search forms definable in the new "smw/schema" namespace [(#3431).](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3431)

It is now possible to define [remote sources which can be queried](https://www.semantic-mediawiki.org/wiki/Help:Remote_request) using special page "Ask" or doing inline queries [(#3167).](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3167)

### Performance

Various effort have been put into improving the performance of the software, most notably with these three code changes:
[#3142](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3142), [#3261](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3261) and [#3286](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3286) with the latter facilitating less expensive paging limits on various user facing special pages via configuration parameter [`$smwgPagingLimit`](https://www.semantic-mediawiki.org/wiki/Help:$smwgPagingLimit).

## Upgrading

Even though Semantic MediaWiki now supports the extension registration approach with "extension.json" (#1732), `enableSemantics` remains the sole point of activiation for SMW itself to ensure that data and objects are prepared in advanced and users do not have to modify any existing settings in their "LocalSettings.php" file.

This release requires (#2065, #2461, #2499) to run the "setupStore.php" or "update.php" script and a missing upgrade process will redirect users to an [error message](https://www.semantic-mediawiki.org/wiki/Help:Upgrade) to remind him or her of a required action. Note that running the schema update may take quite long (minutes on a medium sized site, many hours on a large site).

**Note that SMW requires write access to the code directory meaning that you currently cannot update. This will be fixed in the following relase allowing to configure an alternative directory for this purpose.**

After the upgrade, please check the "Deprecation notices" section on special page "SemanticMediaWiki" to adapt and modify listed deprecated settings.

If you are still using maintenance scripts identifiable by the "SMW_" prefix you must now migrate to the new maintenance script names. See the help pages on [maintenance scrips](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_scripts) for further information.

[#3198](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3198) switched to PHP 5.6 as minimum requirement as well as to MediaWiki 1.27 as minimum requirement.

**Please also carefully read the section on breaking changes and deprecations further down in these release notes. We have also prepared a [migration guide](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_3.0.0/Migration_guide) for you.**

## Miscellaneous

Semantic MediaWiki no longer provides file releases [(See #3347).](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3347) If command line access to the webspace is not available or if the hoster imposes restrictions on required functionality an [individual file release](https://github.com/SemanticMediaWiki/IndividualFileRelease) will have to be created.

## New features and enhancements

### Setup

* [#1732](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1732) Added support for "extension.json"
* [#2916](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2916) Added supplements jobs during the installation process
* [#3095](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3095) Added database upgrade check with ".smw.json"

### Store

* [#2461](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2461) Improved performance on fetching incoming properties
* [#2882](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2882) Added detection of duplicate entities upon storage
* [#2516](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2516) Added an optimization run during the installation process (`setupStore.php`) for SQL tables managed by Semantic MediaWiki
* [#2065](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2065) Added entity specific collation support with help of the [`$smwgEntityCollation`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEntityCollation) setting
* [#2499](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2499) Added [`$smwgFieldTypeFeatures`](https://www.semantic-mediawiki.org/wiki/Help:$smwgFieldTypeFeatures) with `SMW_FIELDT_CHAR_NOCASE` to enable case insensitive search queries
* [#2536](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2536) Added `SMW_FIELDT_CHAR_LONG` as flag for `$smwgFieldTypeFeatures` to extend the indexable length of blob and uri fields to max of 300 chars
* [#2823](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2823) Added `SMW_QSORT_UNCONDITIONAL`
* [#3080](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3080) Added warm up caching for the ID lookup
* [#3142](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3142) Replaced `DISTINCT` with `GROUP BY` in `SQLStore::getPropertySubjects`
* [#3261](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3261) Added support for index hint in `DataItemHandler` to enforce specific index selection
* [#3314](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3314) Moved the `FIXED_PROPERTY_ID_UPPERBOUND` from 50 to 500 to increase the range for fixed property IDs
* [#3353](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3353) Added support in SQLite to drop fields without the need to delete and restore the entire store
* [#3360](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3360) In MySQL/MariaDB increase ID field size from "int(8)" to "int(11)". Postgres and SQLite have no size restriction.
* [#3390](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3390) Adds the `smw_rev` field to the `smw_object_ids` table to track an entity instance and its associated revision ID (represents the raw content)
* [#3397](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3397) MediaWiki removed `Database::nextSequenceValue` in commit wikimedia/mediawiki@0a9c55b#diff-278465351b7c14bbcadac82036080e9f. SMW added this functionality back for the sake of Postgres.

#### ElasticStore

* [#3054](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3054) Added `ElasticStore` to use Elasticsearch as query backend
  - #3237, #3241, #3245, #3247, #3249, #3250, #3253
* [#3152](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3152) Added extra debug query parameter (score_set, q_engine) to special page "Ask"

### Search

* [#2738](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2738) Added information whether `SMWSearch` search mode is enabled or not for special page "Search"
* [#3006](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3006) Disabled default autocompletion for terms starting with `[[` in special page "Search" for the `SMWSearch` type
* [#3096](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3096) Added section title display support to indicate subobjects
* [#3126](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3126) Added extended power profile form
* [#3143](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3143) Hides namespace section and add auto-discovery
* [#3145](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3145) Added simplified term parser to `SMWSearch` (see #3157, #3281)
* [#3234](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3234) Added support for displaytitle in `SearchResult`
* [#3237](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3237) Added support for highlights from external search engine, if available
* [#3419](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3419) Add search autocompletion options when `$wgSearchType = 'SMWSearch';`:
  * `in:Foo bar` equivalent to `[[~~*Foo bar*]]`
  * `phrase:Foo bar` equivalent to `[[~~"Foo bar"]]`
  * `has:Foo bar` equivalent to `[[Foo bar::+]]`

### Query

* [#2398](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2398) Added `#ask` and `#show` parser function support for `@deferred` output mode (see also #3257)
* [#2476](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2476) Added [`$smwgQExpensiveThreshold`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQExpensiveThreshold) and [`$smwgQExpensiveExecutionLimit`](https://www.semantic-mediawiki.org/wiki/Help:$smwgQExpensiveExecutionLimit) to count and restrict expensive `#ask` and `#show` functions on a per page basis
* [#2953](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2953) Added support for natural sort (`n-asc`, `n-desc`) of printout column values
* [#2662](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2662) Added `+depth` as syntax component for a condition to restrict the depth of class and property hierarchy queries
* [#2558](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2558) Added `like:` and `nlike:` comparator operator for approximate queries
* [#2572](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2572) Added `@annotation` as special processing mode to embedded `#ask` queries
* [#2673](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2673) Added the `Query state` special property to be able to track an internal state when a `#ask` uses `@annotation` or `@deferred` as special execution mode. In addition to internal usage, one can also now find all deferred queries with `{{#ask: [[Query state::200]] |format=ul }}`
* [#2873](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2873) Added support for `in:` as expression to the #ask syntax
* [#3125](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3125) Added support for `phrase:` as expression

#### Result formats

* [#2420](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2420) Added support for a datatable output in the `format=table` (and `broadtable`) result printer
* [#2515](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2515) Added support for `#LOCL#TO` date formatting to display a [local time](https://www.semantic-mediawiki.org/wiki/Local_time) offset according to a user preferrence
* [#2677](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2677) Added `+width` as parameter to the `format=table` (and `broadtable`) result printer
* [#2690](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2690) Added the `type` parameter to `format=json` in support for a simple list export
* [#2718](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2718) Added ad-hoc export for the `format=table` datatable
* [#2824](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2824) Added `bom` as parameter to `format=csv`
* [#2826](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2826) Added `valuesep` as parameter to `format=csv` to define a value separator
* [#2822](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2822) Added add `merge` parameter to `format=csv`
* [#2844](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2844) Renamed output formatter `#-ia` to `#-raw`
* [#3024](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3024) Added `format=templatefile` to support individual export formats defined using MediaWiki templates
* [#3009](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3009) Added `#tick` and `#num` output formatter to boolean value type
* [#3011](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3011) Added the [`$smwgDefaultOutputFormatters`](https://www.semantic-mediawiki.org/wiki/Help:$smwgDefaultOutputFormatters) setting to declare default output formatter for a type or property
* [#1315](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1315) Added support for media files to the `feed` printer
* [#3130](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3130) Reworked `list` format
* [#3162](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3162) Added support for `{{DISPLAYTITLE}}` to the `feed` printer
* [#3136](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3136) Added `class` parameter to `list` format

### API

* [#2696](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2696) Added a new `smwbrowse` API module ([#2717](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2717), [#2719](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2719), [#2721](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2721))
* [#3052](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3052) Added `api_version` to ask, askargs API
* [#3129](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3129) Added API `pvalue` browse module
* [#3381](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3381) Added API `psubject` browse module

### Misc

* [#794](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/794) Added `SMW_PARSER_UNSTRIP` to [`$smwgParserFeatures`](https://www.semantic-mediawiki.org/wiki/Help:$smwgParserFeatures) enabling to use unstripped content on a text annotation
* [#2348](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2348) Allow showing annotations even if they are improper for datatype "Text"
* [#2435](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2435) Added filtering of invisible characters (non-printable, shyness etc.) to armor against incorrect annotations
* [#2453](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2453) Changed the approach on how referenced properties during an article delete are generated to optimize the update dispatcher
* [#2471](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2471) Added [`SMW_CAT_REDIRECT`](https://www.semantic-mediawiki.org/wiki/Help:$smwgCategoryFeatures) option to allow finding redirects on categories
* [#2494](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2494) Added [`$smwgChangePropagationProtection`](https://www.semantic-mediawiki.org/wiki/Help:$smwgChangePropagationProtection) and changed the approach on how property modifications are propagated
* [#2543](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2543) Extended [`EditPageHelp`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEnabledEditPageHelp) to be disabled using a user preference
* [#2561](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2561) Added listing of improper assignments to the property page for an easier visual control
* [#2595](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2595) Improved the content navigation in special page "SemanticMediaWiki"
* [#2600](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2600) Added [`$smwgCreateProtectionRight`](https://www.semantic-mediawiki.org/wiki/Help:$smwgCreateProtectionRight) setting to control the creation of new properties and hereby annotations as part of the [authority mode](https://www.semantic-mediawiki.org/wiki/Help:Authority_mode)
* [#2615](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2615) Added `filter=unapprove` to special page "WantedProperties"
* [#2632](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2632) Added [uniqueness violation](https://www.semantic-mediawiki.org/wiki/Help:Property_uniqueness) check on the property page for the property label used
* [#2699](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2699) Added an [input assistance](https://www.semantic-mediawiki.org/wiki/Help:Input_assistance) for the condition textbox on special page "Ask"
* [#2726](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2726) Added entity [input assistance](https://www.semantic-mediawiki.org/wiki/Help:Input_assistance) for editors and the input field on special page "Search"  ([#2756](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2756))
* [#2776](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2776) Added tracking of changes to categories (see 2495)
* [#2785](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2785) Added new styling to property page value list
* [#2796](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2796) Allows "rendering of HTML" on special page "Ask" when using `|headers=plain` in queries
* [#2801](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2801) Added `--skip-optimize` and `--skip-import` to `setupStore.php` (see 2516)
* [#2803](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2803) Filter categories from transcluded content in `format=embedded`
* [#2815](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2815) Added `#nowiki` support for external identifier type
* [#2820](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2820) Added check on declarative property usage
* [#2840](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2840) Added [`$smwgPropertyReservedNameList`](https://www.semantic-mediawiki.org/wiki/Help:$smwgPropertyReservedNameList) to define reserved property names
* [#2842](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2842) Added [`$smwgURITypeSchemeList`](https://www.semantic-mediawiki.org/wiki/Help:$smwgURITypeSchemeList) to restrict valid URI scheme
* [#2861](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2861) Added restriction for a property name that contains a CR, LF
* [#2867](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2867) Added singular, plural category canonical check
* [#2874](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2874) Added grouping support for properties to special page "Browse"
* [#2875](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2875) Changed the theme on special page "Browse" to `smwb-theme-light`
* [#2878](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2878) Added value filter to the property page
* [#2883](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2883) Added function to special page "SemanticMediaWiki" to find duplicate entities
* [#2889](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2889) Added method to make subobject sortkeys distinguishable
* [#2891](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2891) Added flex (responsive) mode to special page "Ask" and special page "Browse" div table
* [#2893](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2893) Changed special page "Ask" appearance ([#2898](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2898))
* [#2895](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2895) Changed display of named subobject caption to appear without an underscore
* [#2906](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2906) Added flex (responsive) mode to the factbox
* [#2907](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2907) Added modal help to special page "Ask"
* [#2913](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2913) Added a job queue watchlist feature and the [`$smwgJobQueueWatchlist`](https://www.semantic-mediawiki.org/wiki/Help:$smwgJobQueueWatchlist) setting
* [#2922](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2922) Added `SMW_BROWSE_SHOW_SORTKEY` flag to the [`$smwgBrowseFeatures`](https://www.semantic-mediawiki.org/wiki/Help:$smwgBrowseFeatures) setting
* [#2930](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2930) Added limit to value selection on the property page
* [#2932](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2932) Added ["removeDuplicateEntities.php"](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_removeDuplicateEntities.php) script to remove duplicate entities
* [#2933](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2933) Added [`$smwgDefaultLoggerRole`](https://www.semantic-mediawiki.org/wiki/Help:$smwgDefaultLoggerRole) setting to define logging granularity for Semantic MediaWiki
* [#2973](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2973) Set initial stats entry for non-fixed predefined properties
* [#3017](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3017) Added the [`$smwgCompactLinkSupport`](https://www.semantic-mediawiki.org/wiki/Help:$smwgCompactLinkSupport) setting to compact links produced by special page "Ask" and special page "Browse"
* [#3019](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3019) Added experimental support for the `SMW_NS_RULE` namespace
* [#3020](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3020) Added the [keyword](https://www.semantic-mediawiki.org/wiki/Help:Type_Keyword) (`_keyw`) type
* [#3029](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3029) Added function to keep updated entities in-memory to improve rebuild performance
* [#3088](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3088) Modernized special page "Page property"
* [#3167](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3167) Added support for `RemoteRequest` to share and consolidate query results from remote sources
* [#3284](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3284) Added the `--dispose-outdated` flag to the "rebuildData.php" maintenance script
* [#3289](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3289) Added support for the JSON format in the `Allows value list` definition
* [#3292](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3292) Added support for bounded intervals, ranges in `Allows value` for number and quantity types
* [#3293](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3293) Added tanslation page annotation (`_TRANS`) support
* [#3308](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3308) Extended content representation on property and concept pages using tabs
* [#3318](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3318) Added `smwgPostEditUpdate` to manage post edit event handling for seconday updates via the API interface
* [#3319](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3319) Sets an extra parser key for queries that contain a self-reference to improve the result display after an edit event
* [#3339](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3339) Added support for uniqueness validation in records/references
* [#3416](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3416) Added support for `<section>` on property pages to put user-defined content into SMW-defined tabs
* [#3415](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3415) Added "compact view" to hide query on special page "Ask"
* [#3429](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3429) Changed default submit method of special page "Ask"  to POST. Submit method can be modified by setting `$smwgSpecialAskFormSubmitMethod` to `SMW_SASK_SUBMIT_GET`, `SMW_SASK_SUBMIT_REDIRECT`, or explicitly setting the default `SMW_SASK_SUBMIT_POST`.
* [#3431](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3431) Moved namespace "Rule" to namespace "smw/schema"
* [#3436](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3436) When an entity is deleted, check for possible open references and keep the ID in case it has a residual reference by turning it into a simple object instance (setting `smw_rev` and `smw_proptable_hash` to null)
* [#3440](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3440) Changed property pages to show the property usage count in the tab
* [#3441](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3441) Added flags to maintenance script "rebuildData.php":
  * `--revision-mode`: Skip entities where its associated revision matches the latests referenced revision of an associated page
  * `--force-update`: Force an update even when an associated revision is known
* [#3443](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3443) Changed job queue job names from `SMW\` prefix to `smw.` prefix. Example: `SMW\UpdateJob` -> `smw.update`

## Bug fixes

* [#481](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/481) Fixed "further results" link with special page "Ask" and templates
* [#502](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/502) Fixed template with named arguments use in #show
* [#839](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/839) Fixed and extended special page "Ask" to be more maintainable
* [#2001](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2001) Fixed issue with `smw_subobject` and the generation of duplicate entities
* [#2505](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2505) Fixed hard-coded default value for `format=csv`
* [#2586](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2586) Fixed class assignments for empty cells in `format=table`
* [#2621](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2621) Fixed sort/order field behaviour in special page "Ask"
* [#2652](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2652) Fixed handling of multiple checkbox parameter in special page "Ask"
* [#2817](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2817) Fixed Fix preg_replace ... unmatched parentheses
* [#2871](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2871) Fixed PHP 7.2 `each` use in `SearchResultSet`
* [#2881](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2881) Fixed display of display dispatched ID in `DataRebuilder`
* [#2884](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2884) Fixed "Cannot use object of type MappingIterator as array"
* [#2896](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2896) Fixed display of inverse indicator for translated predefined properties
* [#2902](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2902) Fixed "LBFactory::getEmptyTransactionTicket ... does not have outer scope"
* [#2909](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2909) Fixed use of `LBFactory::getEmptyTransactionTicket`
* [#2915](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2915) Fixed connection instantiation
* [#2917](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2917) Fixed "DataItemException ... Unserialization failed: the string ..."
* [#2919](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2919) Fixed fetching all entities during a delete
* [#2958](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2958) Fixed to mark subobject entities as done in `ExportController`
* [#2963](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/2963) Fixed recognition of `$wgDBadminuser` in maintenance script "setupStore.php"
* [#2969](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2969) Fixed PHP 7.2 "Warning: count(): Parameter must be an array or an object that implements Countable" issue
* [#3000](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3000) Fixed fetching namespace aliases
* [#3010](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3010) Fixed breaking links in an abbreviated text by the `StringValueFormatter`
* [#3025](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3025) Fixed storage of query information during a `preview` activity
* [#3026](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3026) Fixed replacement of `smw_proptable_hash` during setup
* [#3031](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3031) Fixed duplicate entry `smw_prop_stats` exception
* [#3033](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3033) Fixed "The supplied ParserOptions are not safe ... set $forceParse = true" during the upload of files
* [#3049](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3049) Fixed concept selection
* [#3067](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3067) Fixed processing of simple links containing `|` during the in-text annotation parsing
* [#3076](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3076) Fixed factbox magic works
* [#3082](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3082) Fixed use of `ParserOptions::setEditSection` for MW 1.31
* [#3107](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3107) Fixed recognition of `::=` in `LinksProcessor `
* [#3134](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3134) Escape `/` in property names
* [#3144](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3144) Return IDs as integer when matching all entities
* [#3336](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3336) Fixed issue in special page "Ask" with sort parameter where the first parameter is left empty
* [#3322](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3322) Fixed issue in `UpdateDispatcherJob` with selecting unrelated entities
* [#3336](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3336) Fixed special page "Ask" to recognize first empty sort parameter as page title, e.g. `|sort=,Has foo`
* [#3375](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3375) Fixed error "Invalid sort: title. Must be one of: relevance". MediaWiki default sort type is only `relevance`. SMW added `title`, `recent`, and `best`.
* [#3389](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3389) Fixed "Error: 23505 ERROR: duplicate key value violates unique constraint "smw_new_pkey"" by setting SQL temporary table `id` field to type `SERIAL` instead of `INTEGERY PRIMARY KEY`
* [#3393](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3393) Fixed MW 1.31+ highlighter issue causing extra inline `<p>` which added newlines to display
* [#3413](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3413) Fixed a performance issue for maintenance script "rebuildData.php" by doing `SELECT` on pages+namespaces rather than just pages.

## Breaking changes and deprecations

* [#1345](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1345) Setting multiple values to the `#set` and `#subobject` paser functions using pipe `|` is deprecated. Use the `+sep` parameter instead.
* [#2495](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2495) `Store::getPropertySubjects` and `Store::getAllPropertySubjects` will return an `Iterator` instead of just an array
* [#2588](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2588) Removed special page "SemanticStatistics"
* [#2611](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2611) Removed the user preference `smw-ask-otheroptions-collapsed-info`
* [#2640](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2640) Removed `$smwgAutocompleteInSpecialAsk`
* [#2659](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2659) Removed deprecated constant `SMWDataItem::TYPE_STRING` (replaced by `SMWDataItem::TYPE_BLOB`)
* [#2696](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2696) Soft deprecate the `browsebyproperty` API module, the new `smwbrowse` should be used instead
* [#2705](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2705) Removed usages of deprecated `ResultPrinter::getParameters`
* [#2724](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2724) Added `$smwgUseComparableContentHash` and will be removed with 3.1 to help migrating subobject hash generation
* [#2730](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2730) Replaced `$smwgCacheUsage` settings
* [#2732](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2732) Replaced `$smwgQueryProfiler` settings
* [#2748](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2748) Removed `ContextSource` from `ResultPrinter` instances
* [#2750](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2750) Removed `$smwgSparqlDatabaseMaster` and `$smwfGetSparqlDatabase`
* [#2752](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2752) Renamed `$smwgSparqlDatabaseConnector` to `$smwgSparqlRepositoryConnector`
* [#2761](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2761) Renamed `$smwgDeclarationProperties`
* [#2768](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2768) Changed default setting for `$smwgSparqlRepositoryConnector`
* [#2788](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2788) Resources are now being exported as Internationalized Resource Identifiers (IRI) by default.
* [#2790](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2790) Removed deprecated entry points for maintenance scripts
* [#2802](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2802) Consolidated `$smwgParserFeatures` setting
* [#2806](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2806) Consolidated `$smwgCategoryFeatures` setting
* [#2821](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2821) Consolidated `smwgQSortFeatures` setting
* [#2841](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2841) Replaced `$smwgLinksInValues` with the `SMW_PARSER_LINV` flag now maintained in `$smwgParserFeatures`, PCRE option has been removed
* [#2880](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2880) Migrated special property message keys to new naming schema
* [#2899](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2899) Removed `$smwgScriptPath`
* [#2927](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2927) Removed `SEMANTIC_EXTENSION_TYPE` flag
* [#2944](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2944) Removed deprecated methods in `SMW\DIProperty`
* [#2961](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2961) Renamed `smwAddToRDFExport` hook
* [#2995](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2995) Updated old namespace in Spanish
* [#3164](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3164) Removed `SMW_NS_TYPE` ns and `$smwgHistoricTypeNamespace`
* [#3231](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3231) Consolidated `$smwgPagingLimit` setting
* [#3267](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3267) Removed `SMWQueryProcessor::getSortKeys`
* [#3285](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3285) Deprecated API module `BrowseBySubject`, use `smwbrowse` instead
* [#3307](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3307) Replaced `smwgCacheType` with `smwgMainCacheType`
* [#3315](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3315) Consolidated `smwgSparqlEndpoint` sparql endpoint setting
* [#3366](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3366) Replaced deprecated alias `SMWDIProperty` with `DIProperty` in `SMWDataValue`
* [#3364](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3364) Removed long-deprecated static functions `SMWWikiPageValue::makePage` and `SMWWikiPageValue::makePageFromTitle`
* [#3363](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3363) Removed deprecated `ResultPrinter::$m_params`. Use `ResultPrinter::$params` instead.
* [#3399](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3399) Removed several functions deprecated since SMW 1.9 from `SMW\DataValueFactory`
* [#3401](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3401) Removed long-deprecated functions `ResultPrinter::textDisplayParameters` and `ResultPrinter::exportFormatParameters`
* [#3403](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3403) Removed long-deprecated function `SMWResultArray::getNextObject`
* [#3405](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3405) Removed long-deprecated SMWDIString
* [#3406](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3406) Removed long-deprecated function `SMWRecordValue::getDV`
* [#3407](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3407) Removed deprecated global function `smwfIsSemanticsProcessed`

## Other changes

* [#2342](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2342) Added the display of invalid data value annotations  for datatype "Text"
* [#2485](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2485) Disabled updates by the `QueryDependencyLinksStore` on a 'stashedit' activity
* [#2491](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2491) Added `ChunkedIterator` to `DataRebuilder` to avoid OOM situations in case of a large update queue
* [#2535](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2535) Fixed property namespace (`_wpp`) display in `WikiPageValue`
* [#2540](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2540) Added type `parser-html` to [`JSONScript`](https://www.semantic-mediawiki.org/wiki/Help:Integration_tests) testing to allow assertions on HTML structure
* [#2591](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2591) Discontinued reading MediaWiki `job` table, use the `JobQueue::getQueueSizes` instead
* [#2609](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2609) Added check to special page "Ask" to require JavaScript
* [#2631](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2631) Disabled purge button while JS resources are still loaded
* [#2650](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2650) Replaced some styles in special page "Ask"
* [#2653](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2653) Fixed `broadtable` width with the "MobileFrontend" extension
* [#2676](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2676) Added support for column default values in the `TableBuilder`
* [#2680](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2680) Added `null_count` column to `PropertyStatisticsTable`
* [#2691](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2691) Replaced `#info` icon set
* [#2698](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2698) Added persistent caching to the `HierarchyLookup`
* [#2714](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2714) Added `SMW::GetPreferences` hook
* [#2727](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2727) Moved parameter processing from `QueryProcessor` to `ParamListProcessor`
* [#2745](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2745) Moved `ResultPrinter` base class
* [#2747](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2747) Moved `TableResultPrinter`
* [#2751](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2751) Added `RecursiveTextProcessor` to isolate `$wgParser` access in `ResultPrinter`
* [#2765](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2765) Added `SMW::Setup::AfterInitializationComplete` hook
* [#2774](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2774) Moved `SMWQueryParser` to `SMW\Query\Parser`
* [#2783](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2783) Added `JsonSchemaValidator`
* [#2785](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2785) Moved `PropertyPage` and `ConceptPage`
* [#2845](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2845) Extended use of cached hierarchy instance
* [#2847](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2847) Introduced different approach to update query dependencies
* [#2888](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2888) Introduced `Setup::initExtension` to allow an early registration of `SpecialPage_initList` and `ApiMain::moduleManager` hooks
* [#2908](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2908) Refactored the `ConnectionProvider`
* [#2928](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2928) Moved `SQLStore::fetchSemanticData` to `SemanticDataLookup`
* [#2972](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/2972) Added `SMW::SQLStore::EntityReferenceCleanUpComplete` hook
* [#3032](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3032) Added the `SMW::LinksUpdate::ApprovedUpdate` and `SMW::Parser::ChangeRevision` hook
* [#3061](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3061) Added detection of changes emitted by the `BlockIpComplete`, `UnblockUserComplete`, and `UserGroupsChanged` hook
* [#3063](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3063) Moved import files to data folder
* [#3070](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3070) Added `SMW::Admin::TaskHandlerFactory` hook
* [#3131](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3131) Added `CONTENT_MODEL_RULE` to be able to do schema validation before a save sometime in the future. Switching to an alternate model at a later stage would only create headaches.
* [#3138](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3138) Fixed use of `$wgExtensionDirectory` to find SMW's "extension.json"
* [#3146](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3147) Moved table hash cache handling
* [#3160](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3160) Moved `FeedExportPrinter` and added integration test
* [#3260](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3260) Moved `SMWSQLStore3::changeSMWPageID`
* [#3275](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3275) Moved `SMWSQLStore3Readers::getPropertySubjects`
* [#3282](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3282) Moved `SMWSQLStore3Readers::getProperties`
* [#3384](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3384) Isolated the handling of "ALTER SEQUENCE ..." for Postgres
* [#3432](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3432) Moved `SMW\CategoryResultPrinter` to `SMW\Query\ResultPrinters\CategoryResultPrinter`

## Contributors

- 1036 - James Hong Kong
-  147 - translatewiki.net for the translator community
-  120 - Karsten Hoffmeyer
-   50 - Jeroen De Dauw
-   13 - Stephan Gambke
-    7 - Kumioko
-    6 - Iván
-    6 - Zoran Dori
-    4 - James Montalvo
-    4 - Máté Szabó
-    2 - Jaider Andrade Ferreira
-    2 - Josef Konrad
-    2 - TK-999
-    1 - Amir E. Aharoni
-    1 - C. Scott Ananian
-    1 - Kunal Mehta
-    1 - Peter Grassberger
-    1 - Prateek Saxena
-    1 - Stephan
-    1 - Thiemo Kreuz
-    1 - Timo Tijhof
-    1 - Toni Hermoso Pulido
-    1 - ka7
-    1 - matthew-a-thompson
-    1 - salle
-    1 - غلامحسین حق دوست
