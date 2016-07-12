# Semantic MediaWiki 2.4

Released on July 9th, 2016.

## Highlights

### Support for multiple languages

Added support for [multilingual content](https://www.semantic-mediawiki.org/wiki/Localization_and_multilingual_content).
This includes the introduction of the [monolongual text datatype](https://www.semantic-mediawiki.org/wiki/Help:Type_Monolingual_text),
a new [special property to describe properties](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Has_property_description)
and the new [Semantic Interlanguage Links extension](https://www.semantic-mediawiki.org/wiki/Extension:Semantic_Interlanguage_Links).

### Pattern based constraints

Added support for constraint specification using regular expressions (#1417). The use of `regular
expressions` and thus the `Allows pattern` property to express a constraint assignment is restricted
to users with the [`smw-patternedit`](https://www.semantic-mediawiki.org/wiki/Help:Permissions_and_user_rights) right.

### Positional units

It is now possible to specify which position a [custom unit](https://www.semantic-mediawiki.org/wiki/Help:Custom_units)
should have in [Corresponds to](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Corresponds_to) annotations.
This means you can specify `[[Corresponds to::€ 1]]` instead of `[[Corresponds to::1 €]]`. You can find a
[small example](http://sandbox.semantic-mediawiki.org/wiki/Issue/1329_(Positional_unit_preference)) on the Sandbox.

### Display precision

You can now specify the precision used for display of numeric properties (i.e. those of type Number,
Quantity, Temperature). This is done using the
[Display precision of](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Display_precision_of)
property. You can override this display precision per `#ask` query, by using `-p<digit>`.
You can [view the examples](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1335).

### Enhanced date and time formatting

Extended [date and time formatting](https://www.semantic-mediawiki.org/wiki/Help:Type_Date)
by supporting PHP's `DateTime` format rules.

### Display Title storage

MediaWiki's `{{DISPLAYTITLE:...}}` can now be stored as the
[Display title of](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Display_title_of)
special property, so it can be used in queries.


## Compatibility changes

Support was added for MediaWiki 1.26 and MediaWiki 1.27. SMW 2.3 has know issues with these versions
of MediaWiki, so you are highly encouraged to upgrade SMW if you plan to use one of them. While SMW
2.3 already had beta support for PHP 7, this release fully supports it.

This release does not drop support for anything. It is however the last release to support PHP older
than 5.5 and MediaWiki older than 1.25.

For more information, see the [compatibility overview](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/COMPATIBILITY.md).


## New features and enhancements

* [#498](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/498) Extended `rebuildData.php` to remove outdated entity references (see `PropertyTableIdReferenceDisposer`)
* [#1243](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1243) Made failed queries discoverable
* [#1246](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1246) Added support for `~`/`!~` on single value queries (example: `{{#ask: [[~Foo/*]] }}`)
* [#1267](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1267) Added the `browseByProperty` API module to fetch a property list or individual properties via the WebAPI
* [#1268](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1268) Restored compliance with MediaWiki's 1.26/1.27 WebAPI interface to ensure continued support for the `ask` and `askargs` output serialization
* [#1257](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1257) Changed import of recursive annotations (#1068) from the format to a query level using the `import-annotation` parameter
* [#1291](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1291) Added support for range queries such as `[[>AAA]] [[<AAD]]`
* [#1293](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1293) Added `_ERRC` and `_ERRT` as pre-defined properties to aid error analysis
* [#1299](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1299) Added dot named identifier restriction for subobject names containing a dot (`fooba.bar` reserved for extensions)
* [#1313](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1313) Added usage count information to property pages
* [#1321](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1321) Added [`$smwgSparqlRepositoryConnectorForcedHttpVersion`](https://semantic-mediawiki.org/wiki/Help:$smwgSparqlRepositoryConnectorForcedHttpVersion) setting to force a specific HTTP version in case of a #1306 cURL issue
* [#1290](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1290) Added support for properties and `prinrequests` to be forwarded to a redirect target if one exists
* [#1329](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1329) Added positional preference for units when declared in `Corresponds to` (¥ 500 vs 500 JPY)
* [#1350](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1350) Enlarged input field on special page "Browse"
* [#1335](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1335) Added possibility to specify a display precision for a numeric `datatype` by either denoting a [`Display precision of`](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Display_precision_of) or using `-p<number of digits>` as `#ask` printout option
* [#1344](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1344) Added `MonolingualTextValue` and `LanguageCodeValue`
* [#1361](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1361) Added `--with-maintenance-log` option to `rebuildData.php`, `rebuildPropertyStatistics.php`, and `rebuildConceptCache.php`
* [#1381](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1381) Added localizable context help for properties using the predefined property `Has property description` (which is specified as `MonolingualText` type)
* [#1389](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1389) Added free date/time formatting support using the `-F[ ... ]` option
* [#1391](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1391) Made subobject directly browsable from/in the Factbox
* [#1396](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1396) Explicitly annotated years now have an `AC/CE` era indication
* [#1397](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1397) Added support for microseconds in `DITime`
* [#1401](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1401) Added support for parsing `年/月/日` date format in `DITime`
* [#1407](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1407) Added quick result download links to `Special:Ask`
* [#1410](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1410) Added support for `{{DISPLAYTITLE:title}}` caption using the [`Display title of`](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Display_title_of) property
* [#1417](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1417) Added [`Allows pattern`](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Allows_pattern) property to define a value constraint using regular expressions and the required `smw-patternedit`right to add those expressions
* [#1433](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1433) Added `--ignore-exceptions` and `exception-log` options to `rebuildData.php` while option `-v` is showing additional information about the processed entities
* [#1440](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1440) Added various changes to accommodate MW 1.27
* [#1463](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1463) Added support for the [`Has uniqueness constraint`](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Has_uniqueness_constraint) property trait
* [#1474](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1474) Added a search link for zero properties to the `Special:Properties`
* [#1483](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1483) Added statistics about [outdated entities](https://www.semantic-mediawiki.org/wiki/Help:Outdated_entities) to the `Special:Statistics`
* [#1542](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1542) Extended the query parser to support conditions with object values that contain `=` (#640)
* [#1545](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1545) Added `#LOCL` as `TimeValue` output format
* [#1570](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1570) Added ["Object ID disposal"](https://www.semantic-mediawiki.org/wiki/Help:Object_ID_disposal) `to Special:SMWAdmin`
* [#1572](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1572) Extended the query parser to support property chaining on subtypes
* [#1580](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1580) Added `#LOCL` as `BooleanValue` output format
* [#1591](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1591) Added `#LOCL` as `NumberValue` output format
* [#1626](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1626) Added `$GLOBALS['smwgQueryDependencyAffiliatePropertyDetectionlist']` to monitor affiliate properties required for initiating a query dependency update


## Bug fixes

* [#541](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/541) Fixed duplicate column when "further results ..." are redirected to `Special:Ask`
* [#753](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/753) Fixed number parsing of non-zero lead decimal numbers (.1 vs 0.1) / (T40476)
* [#1244](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1244) Find redirect for a property when specified as a record field (in `PropertyListValue`)
* [#1248](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1248) Fixed misplaced replacement of `_` in the `ImportValueParser`
* [#1270](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1270) Fixed printout display of inverse properties
* [#1272](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1272) Fixed serialization of `_rec` type in the `QueryResultSerializer`
* [#1275](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1275) Fixed export of record type data when embedded in a subobject
* [#1286](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1286) Fixed support for sorting by category
* [#1287](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1287) Fixed exception for when `$smwgFixedProperties` contains property keys with spaces
* [#1289](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1289) Fixed redirect statement for resources matched to an import vocabulary (`SPARQL` query)
* [#1301](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1301) Fixed `count` query result discrepancy (to exclude redirect and deleted entities)
* [#1314](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1314) Fixed hidden annotation copy of `[[ :: ]]` text values when embedded in query results
* [#1318](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1318) Fixed possible `null` object in `AskParserFunction` when creating a `QueryProfile`
* [#1357](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1357) Fixed `|+align=...` usage for `format=table`
* [#1358](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1358) Fixed recognition of multi-byte boolean value
* [#1348](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1348) Fixed single year detection in `TimeValue`
* [#1414](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1414) Fixed exception caused by a missing message page on a `Service link` annotation
* [#1449](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1449) Fixed mapping of imported URI to an internal `DataItem`
* [#1450](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1450) Fixed export of concept
* [#1453](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1453) Fixed off/on display in text value
* [#1459](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1459) Fixed column display regression in `CategoryResultPrinter` for subobjects
* [#1466](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1466) Fixed remote resource path detection that appeared in connection with a non-default extension setup
* [#1473](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1473) Fixed exception caused by `ParameterInput` due to "HTML attribute value can not contain a list of values"
* [#1477](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1477) Fixed query result from `SPARQLStore` to filter redirects natively
* [#1489](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1489) Fixed fatal error in `RdfResultPrinter` due to namespace mismatch
* [#1496](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1496) Fixed concept handling for `postgres`
* [#1513](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1513) Fixed rendering of text properties containing wikitext lists
* [#1526](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1526) Fixed `_` handling for value strings submitted to the `Special:SearchByProperty`
* [#1550](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1550) Fixed `SPARQLStore` `XML` response parsing for strings that contain UTF-8 characters
* [#1562](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1562) Fixed fatal error in `FeedResultPrinter` due to usage of an interwiki assignment
* [#1568](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1568) Fixed usage of invalid characters/tags in property name
* [#1594](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1594) Fixed handling of numbers with scientific notation in `Special:SearchByProperty`
* [#1597](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1597) Fixed possible ID collision in `DependencyLinksTableUpdater`
* [#1598](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1598) Fixed content language setting for `InfoLinks`
* [#1589](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1589) Fixed display precision constraint during condition building
* [#1608](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1608) Fixed that a `#info` without a message will create an empty tooltip or when used as `<info />` causing a failure
* [#1610](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1610) Fixed a potential exception in the `postgres` implementation when creating temporary tables
* [#1628](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1628) Fixed exception when `NumberValue` tries to use a `NULL` as numeric value.
* [#1638](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1638) Fixed possible invalid property in case the label contains `[`


## Internal changes

* [#1235](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1235) Improve query performance in `PropertyUsageListLookup`
* [#1023](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1023) Split the `DocumentationParserFunction`
* [#1264](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1264) Removed `pg_unescape_bytea` special handling for `postgres` in the `ResultPrinter`
* [#1276](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1276) Extended `QueryResultSerializer` (relevant for the API output) to export the raw output of a time related value
* [#1281](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1281) Extended `QueryResultSerializer` to export the internal property key
* [#1291](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1291) Added `DescriptionProcessor` to isolate code path from the `SMWQueryParser`
* [#1319](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1319) Switched from Sesame 2.7.14 to 2.8.7 in the CI environment
* [#1382](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1382) Added `DispatchingDataValueFormatter` and `ValueFormatterRegistry`
* [#1385](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1385) Added `StringValueFormatter` and `CodeStringValueFormatter`
* [#1388](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1388) Added `TimeValueFormatter`
* [#1421](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1421) Added `DeferredDependencyLinksUpdater` to avoid violations reported by `TransactionProfiler` in MW 1.26+
* [#1417](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1417) Added `PermissionPthValidator` together with new the `smwcurator` group and `smw-patternedit` right
* [#1435](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1435) Added `DeferredCallableUpdate` (together with `$GLOBALS['smwgEnabledDeferredUpdate']`) to support MW's `DeferrableUpdate` interface (i.e. to support queuing DB related transactions)
* [#1445](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1445) Added `userlang` as `ParserOutput` option
* [#1451](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1451) Added `ExtraneousLanguage` interface
* [#1460](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1460) Requires PHP extension mbstring in `composer.json`
* [#1482](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1482) Added the `SMW::FileUpload::BeforeUpdate` hook
* [#1512](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1512) Fixed test suite to support PHP7
* [#1575](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1575) Removed `smw_subobject` from `PropertyListLookup` query
* [#1591](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1591) Added `IntlNumberFormatter`
* [#1593](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1593) Added `NumberValueFormatter`
* [#1601](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1601) Added `InfoLinksProvider`
* [#1606](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1606) Disabled DB transactions in `QueryEngine` to avoid potential issues when creating temporary tables
* [#1626](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1626) Added `EntityIdListRelevanceDetectionFilter` and `TemporaryEntityListAccumulator` in #1627
* [#1635](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1635) Switched from Fuseki 1.1.0 to 2.4.0 in the CI environment
* Most updates now occur in [deferred mode](https://www.semantic-mediawiki.org/wiki/Deferred_updates)
to conform with [T92357](https://phabricator.wikimedia.org/T92357) Extensions that wish to extend
data objects are encouraged to use hooks and avoid conflicts when updates are queued.


## Contributors

* James Hong Kong
* Jeroen De Dauw
* Karsten Hoffmeyer
* Felipe de Jong
* Florian Schmidt
* Niklas Laxström
* Ahmad Gharbeia
* Stephan Gambke
* Amir E. Aharoni
* Siebrand Mazeland
* Cindy Cicalese
* Hangya
* Sébastien Beyou
* Aaron Schulz
* Jaider Andrade Ferreira
* Kunal Mehta
* Ori Livneh
* Peter Grassberger
* Reedy
* Vitaliy Filippov
* Wolfgang Fahl
* Alexander Gesinn
* TranslateWiki.net translators
