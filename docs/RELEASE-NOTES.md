# Semantic MediaWiki 1.9

THIS IS NOT A RELEASE YET

Semantic MediaWiki 1.9 is currently in beta-quality and is not recommended for use in
production until the actual release.

### Requirements changes

* Changed minimum MediaWiki version from 1.17 to 1.19.
* Changed minimum PHP version from 5.2. to 5.3.2.
* Full compatibility with MediaWiki 1.19, 1.20, 1.21, 1.22 and forward-compatibility with 1.23.
* Changed minimum Validator version from 0.5 to 1.0.

### New features

* SMW (and its dependencies) can now be installed via Composer
* Added maintenance script to rebuild the property statistics
* (271864f) The property type String is now an alias for Text and has no more length restrictions
* (9714d04) \SMW\ApiBrowse enables to browse a subject via the Api
* Special:Statistics now shows a "semantic statistics" version (only when using a recent version of MediaWiki)
* Special:Properties now has a search form
* An smw-admin right is now available for more fine grained access control to Special:SMWAdmin
* The smwinfo API module now provides additional information
* The new "browsebysubject" API module allows fetching the semantic data for a given page
* Added Special:Concepts special page that lists the available concepts

### Enhancements

* (Bug 36309) Enable +sep= as multiple values identifier in #set
* (Bug 39019) Enable +sep= as multiple values identifier in #subobject
* (Bug 34477) Display cache information in concept pages
* (Bug 34172) Add individual CSS class for further result links allowing to add auxiliary information (icon etc.)
* (I2e509e08) Improved efficiency of property statistics rebuilding script
* (Bug 43932) Enable html tags support for non-list results in SMW\ListResultPrinter
* (Bug 44275) Enable .data( 'sortkey' ) support in SMW\ListResultPrinter

* (eb764db) Add \SMW\PropertyAnnotatorDecorator
* (e0f3f4d) Refactor	\SMW\RefreshJob
* (2a44b00) Register #show as callback
* (f33fd12) Add \SMW\ExtensionContext and \SMW\ContextAware
* (a33411f) Add \SMW\Api\BrowseBySubject
* (40e7572) Renamed SMWDISerializer to \SMW\Serializers\QueryResultSerializer
* (a0b08fe) Add \SMW\Serializes\SemanticDataSerializer
* (02635a1) Replace SkinTemplateToolboxEnd hook with SMW\BaseTemplate
* (a7351c5) Register #ask as callback
* (058c2fc) Separate extension registration using SMW\Setup
* (5a82da8) Improve SMW\Factbox and introduce SMW\FactboxCache ($smwgFactboxUseCache, $smwgFactboxCacheRefreshOnPurge)
* (24cca37) introducing SMW\Test\MockObjectBuilder and SMW\Test\MockObjectRepository
* (ed52df7) (Bug 50844) Special:Properties search form
* (332982b) SMW\Subobject, SMW\SubobjectParserFunction use SMW\HashIdGenerator
* (ec5dd46) Introducing SMW\SimpleDependencyBuilder and SMW\SharedDependencyContainer
* (18d17a5) Add SMW\StoreUpdater
* (71dbba1) Add SMW\ObservableDispatcher
* (6d5a3c5) Add SMW\JobBase
* (dc28899) Add SMW\UpdateDispatcherJob
* (be56922) Add SMW\FunctionHookRegistry
* (c8a2f97) SMW\ApiAsk + SMW\ApiAskArgs fix bug 51091
* (8bcee83) (Bug 44696) AskApi to support valid XML
* (a949f04) (Bug 33181) Special:Concepts
* (92b67bd) Use SMW\TableFormatter for the table query printer
* (6c06567) SMW\SQLStore\PropertyTableDefinitionBuilder
* (b8aea6c) (Bug 48840) Create "smw-admin"
* (2164a25) SMW\SQLStore\StatisticsCollector
* (87b214f) SMW\Settings
* (5a33d2d) SMW\CacheHandler
* (3e706ef) (Bug 47927) $concept->getCacheStatus()
* (0c971f8) (Bug 46458) ApiSMWInfo add count information
* (bb35e8a) (Bug 47123) Aggregate numbers based on the mainlabel
* (5cda766) (Bug 46930) changeTitle only create redirect
* (6dd845e) (Bug 34477) SMW\ConceptPage display concept cache info
* (f216a41) ParserHook re-factoring
* (38499a8) Display statistics using the SpecialStatsAddExtra hook
* (e4a5fb8) (Bug 31880) add column class (based on dataValue typeId)
* (e4a2035) SMW\RecurringEvents eliminate restrictions and use subobj
* (a957596) SMW\JSONResultPrinter delete clutter
* (395b584) Add ResourceLoaderGetConfigVars
* (7c60e50) SMW\ApiResultPrinter
* (cb6c6ad) SMW\ResultPrinter class turn RequestContext aware
* (7d1e2ad) (Bug 34782) Add note to #info parser function

### Bug fixes

* The property statistics rebuilding is no longer done whenever you run update.php.
* (Bug 42321) Fixed issue frequently causing notices in SQLStore3
* (5fdbb83) Fix offset display in Special:Ask
* (9113ad1) (Bug 47010) SMWInfoLink
* (af0cbe0) Fix escaping issue on Special:Ask
* (ba74804) Fix construction of SMWExpLiteral
* (d16a103) (Bug 45053) SMW\ListResultPrinter support _qty tooltip display
* (9b2b5c7) (Bug 44518) Don't display <li> elements for |format=list
* (f226c5a) SMW\ListResultPrinter (bug 43932) + (bug 44275)
* (fcb7da9) (Bug 42324) fix sqlite support in sqlstore3
* (3507f84) (Bug 21893) Fixed queries that use the like comparator for properties with a restricted set of values

### Dropped features

* (6f7625f) Remove Special:QueryCreator
* (5a3f6ed) (Bug 50755) Remove MigrationJob/SMWMigrate
* (f9cff2b) Remove smwfLoadExtensionMessages

### Compatibility changes

* Deleted pre SMW 1.5.1 entry point (includes/SMW_Settings.php), the main entry point is SemanticMediaWiki.php
* (I17a3e0) Support for quantity export via API and JSON format
* (50c5109) Removed old storage implementation SMWSQLStore2, superseded by SMWSQLStore3 in SMW 1.8
* (I5db911) #set_recurring_event using subobjects (changes query behavior for recurring events; for more see [[Help:Recurring_events]])

### Deprecated methods/classes

If not noted otherwise, deprecated methods or classes will be removed in SMW 1.11.

* (b4664be) smwfIsSemanticsProcessed was replaced by SMW\NamespaceExaminer
* (3ba701f) smwfEncodeMessages was replaced by SMW\Highlighter, SMW\MessageFormatter
* (534548b) smwfGetStore was replaced by SMW\StoreFactory
* SMWParseData was replaced by a non-static SMW\ParserData class

* SMWListResultPrinter, SMWResultPrinter, SMWSubobject, SMWSet
* SMWFeedResultPrinter, SMWDISerializer
* SMWDIString, SMWStringLengthException, SMWSetRecurringEvent

### Platform stability

* Over 80 PHPUnit tests have been added
* Over 10 QUnit tests have been added
* The tests now [run on TravisCI](https://travis-ci.org/SemanticMediaWiki/SemanticMediaWiki)
** Compatibility is now ensured against all supported MediaWiki and PHP versions
** Compatibility is now ensured for all supported databases

### Documentation

The documentation bundled with the SMW source code has been updated. It can be found in
[the docs folder](docs).

### Extended translations

As usual, translations have been extended thanks to the [[Translatewiki.net|Translatewiki.net project]].
In addition, the core strings (SMW properties and datatypes) for Slovak have been updated.
