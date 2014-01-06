# Semantic MediaWiki 1.9

Released January 3rd, 2014.

### Platform compatibility changes

* Changed minimum PHP version from 5.2. to 5.3.2.
* Changed minimum MediaWiki version from 1.17 to 1.19.
* Full compatibility with MediaWiki 1.19, 1.20, 1.21, 1.22 and forward-compatibility with 1.23.
* Changed minimum Validator version from 0.5 to 1.0.
* Added beta support for PostgreSQL.

### New features

* SMW (and its dependencies) can now be installed via [Composer](https://getcomposer.org/)
* Added maintenance script [rebuildPropertyStatistics.php](https://semantic-mediawiki.org/wiki/Help:RebuildPropertyStatistics.php) to rebuild the property statistics
* (271864f) The property type String is now an alias for Text and has no more length restrictions
* (38499a8) Special:Statistics now shows a "semantic statistics" version (only when using a recent version of MediaWiki)
* (ed52df7) (Bug 50844) Special:Properties now provides a form to search user-defined properties
* (a949f04) (Bug 33181) Add page [Special:Concepts](https://semantic-mediawiki.org/wiki/Help:Special:Concepts) that lists available concepts
* (0c971f8) (Bug 46458) Extend smwinfo API module to provide additional information
* (b8aea6c) (Bug 48840) Add a smw-admin right to enable restricted access to Special:SMWAdmin
* (9714d04) (a33411f) Add new "browsebysubject" API module to fetch semantic data for a given subject

### Enhancements

* (Bug 36309) and (Bug 39019) Add +sep= as multiple value separator for #set and #subobject parser function
* (6dd845e) (Bug 34477) Add cache information to concept pages
* (Bug 34172) Add [individual CSS class](https://www.semantic-mediawiki.org/wiki/Help:Further_results) injection for further result links
* (I2e509e) Improved efficiency of property statistics rebuilding script
* (8bcee83) (Bug 44696) Fix XML output for AskApi
* (bb35e8a) (Bug 47123) Aggregate numbers based on the label/group
* (5cda766) (Bug 46930) SMWSQLStore3Writers::changeTitle only create redirects when appropriate
conditions are met
* (e4a5fb8) (Bug 31880) Formatting numbers in tables based based on a customizable typeId
* (e4a2035) Modify SMW\RecurringEvents to use a subobject as datamodel to represent
individual events within a page
* (7d1e2ad) (Bug 34782) Add note parameter to #info parser function

#### New configuration parameters

* [$smwgEnabledSpecialPage](https://semantic-mediawiki.org/wiki/Help:$smwgEnabledSpecialPage)
* [$smwgQueryProfiler](https://semantic-mediawiki.org/wiki/Help:$smwgQueryProfiler)
* [$smwgShowHiddenCategories](https://semantic-mediawiki.org/wiki/Help:$smwgShowHiddenCategories)
* [$smwgFactboxUseCache](https://semantic-mediawiki.org/wiki/Help:$smwgFactboxUseCache), [$smwgFactboxCacheRefreshOnPurge](https://semantic-mediawiki.org/wiki/Help:$smwgFactboxCacheRefreshOnPurge)
* [$smwgPropertyZeroCountDisplay](https://semantic-mediawiki.org/wiki/Help:$smwgPropertyZeroCountDisplay), [$smwgPropertyLowUsageThreshold](https://semantic-mediawiki.org/wiki/Help:$smwgPropertyLowUsageThreshold)
* [$smwgFixedProperties](https://semantic-mediawiki.org/wiki/Help:$smwgFixedProperties)
* [$smwgAutoRefreshOnPageMove](https://semantic-mediawiki.org/wiki/Help:$smwgAutoRefreshOnPageMove), [$smwgAutoRefreshOnPurge](https://semantic-mediawiki.org/wiki/Help:$smwgAutoRefreshOnPurge)
* [$smwgCacheType](https://semantic-mediawiki.org/wiki/Help:$smwgCacheType), [$smwgCacheUsage](https://semantic-mediawiki.org/wiki/Help:$smwgCacheUsage)

### Bug fixes

* The property statistics rebuilding is no longer done whenever you run update.php.
* (Bug 42321) Fixed issue frequently causing notices in SQLStore3
* (5fdbb83) Fix offset display in Special:Ask
* (9113ad1) (Bug 47010) SMWInfoLink
* (af0cbe0) Fix escaping issue on Special:Ask
* (ba74804) Fix construction of SMWExpLiteral
* (d16a103) (Bug 45053) Fix quantity display support in SMW\ListResultPrinter
* (9b2b5c7) (Bug 44518) Do not display &#60;li&#62; elements for |format=list
* (Bug 43932) Fix html tag support for non-list results in SMW\ListResultPrinter
* (Bug 44275) Fix .data( 'sortkey' ) support in SMW\ListResultPrinter
* (fcb7da9) (Bug 42324) fix SQlite support in sqlstore3
* (3507f84) (Bug 21893) Fixed queries that use the like comparator for properties with a restricted
set of values

### Compatibility changes

* Deleted pre SMW 1.5.1 entry point (includes/SMW_Settings.php), the main entry point is SemanticMediaWiki.php
* (I17a3e0) Support for quantity export via API and JSON format
* (50c5109) Removed old storage implementation SMWSQLStore2, superseded by SMWSQLStore3 in SMW 1.8
* (I5db911) #set_recurring_event using subobjects (changes query behavior
for recurring events; for more see the help page on [recurring events](https://semantic-mediawiki.org/wiki/Help:Recurring_events "Recurring events")

### Deprecated classes

If not noted otherwise, deprecated methods or classes will be removed in SMW 1.11.

* (b4664be) smwfIsSemanticsProcessed was replaced by SMW\NamespaceExaminer
* (3ba701f) smwfEncodeMessages was replaced by SMW\Highlighter, SMW\MessageFormatter
* SMWParseData was replaced by a non-static SMW\ParserData class
* SMWListResultPrinter, SMWResultPrinter, SMWSubobject, SMWSet
* SMWFeedResultPrinter, SMWDISerializer
* SMWDIString, SMWStringLengthException, SMWSetRecurringEvent

### Added or changed classes 

* (eb764db) Add SMW\PropertyAnnotatorDecorator for handling individual "standard" properties
* (f33fd12) Add SMW\ExtensionContext and \SMW\ContextAware
* (40e7572) Renamed SMWDISerializer to \SMW\Serializers\QueryResultSerializer
* (a0b08fe) Add SMW\Serializes\SemanticDataSerializer in order for SemanticData to be serializable
* (02635a1) Replace SkinTemplateToolboxEnd hook with SMW\BaseTemplate
* (ec5dd46) Add SMW\SimpleDependencyBuilder and SMW\SharedDependencyContainer as simple framework that
allows for individual object factoring and dependency injection
* (92b67bd) Add SMW\TableFormatter for the table query printer
* (5a33d2d) Add SMW\CacheHandler to separate MediaWiki specific cache injection
* (395b584) Add ResourceLoaderGetConfigVars to populate SMW related configuration details for JavaScript
* (7c60e50) Add SMW\ApiResultPrinter to support query printers to use Ajax/WebApi interface for
query result updates
* (cb6c6ad) SMW\ResultPrinter class turn RequestContext aware

The following classes and interfaces were re-factored and/or added in order to promote testability:

* (e0f3f4d) Rename and re-factor \SMW\RefreshJob
* (I3b41d4) Rename and re-factor \SMW\UpdateJob
* (6d5a3c5) Add SMW\JobBase to enable dependency injection
* (058c2fc) Add SMW\Setup to separate extension registration and initialization
* (87b214f) Add SMW\Settings class to remove GLOBAL state and enable injection of individual configuration
details during runtime
* (5a82da8) Re-factor SMW\Factbox and add SMW\FactboxCache to minimize content parsing
* (24cca37) Add SMW\Test\MockObjectBuilder to easily manage MW and SMW mock objects
* (71dbba1) Add SMW\ObservableDispatcher to enable Observes to act as an observable subject itself
* (dc28899) (18d17a5) Add SMW\StoreUpdater, SMW\UpdateDispatcherJob, and SMW\PropertyTypeComparator
to separate responsibilities during the update
* (6c06567) Add SMW\SQLStore\PropertyTableDefinitionBuilder to separate build definition
* (2164a25) Add \SMW\ResultCollector interface to support cacheable results when executing Special:Statistics or Special:Properties
* (c8a2f97) (Bug 51091) Rename and re-factor SMW\Api\Ask and SMW\Api\AskArgs

### Removed features

* (6f7625f) Remove Special:QueryCreator
* (5a3f6ed) (Bug 50755) Remove MigrationJob/SMWMigrate
* (f9cff2b) Remove smwfLoadExtensionMessages
* (a957596) SMW\JsonResultPrinter remove obsolete serialization


### Platform stability

* Over 130 PHPUnit tests have been added
* Over 10 QUnit tests have been added
* The tests now [run on TravisCI](https://travis-ci.org/SemanticMediaWiki/SemanticMediaWiki)
    * Compatibility is now ensured against all supported MediaWiki and PHP versions
    * Compatibility is now ensured for all supported databases

### Documentation

The documentation bundled with the SMW source code has been updated. It can be found in the docs folder.

### Extended translations

As usual, translations have been extended thanks to the [Translatewiki.net project](https://translatewiki.net).
In addition, the core strings (SMW properties and datatypes) for Slovak have been updated.
