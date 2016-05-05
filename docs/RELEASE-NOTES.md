# Semantic MediaWiki 2.4

Not a release yet.

## Highlights

* Added positional preference for units (#1329)
* Added a possibility to specify a fixed precision for numeric datatypes (#1335)
* Added support for monolingual text datatype where a specific language (as code) can be added to a text value (#1344, #1381)
* Extended date/time query output formatting by supporting PHP's `DateTime` format rules (#1389)
* Added support for constraint specification using regular expressions (#1417). The use of `regular expressions` and thus the `Allows pattern` property to express a constraint assignment is restricted to users with the [`smw-patternedit`](https://www.semantic-mediawiki.org/wiki/Help:Permissions_and_user_rights) right.
* Added support for `{{DISPLAYTITLE:...}}` (#1410)

## New features and enhancements

* #498 Extended `rebuildData.php` to remove outdated entity references (see `PropertyTableIdReferenceDisposer`)
* #1243 Make failed queries discoverable
* #1246 Added support for `~`/`!~` on single value queries
* #1267 Added the `browseByProperty` API module to fetch a property list or individual properties via the WebAPI
* #1268 Restored compliance with MediaWiki's 1.26/1.27 WebAPI interface to ensure continued support for the `ask` and `askargs` output serialization
* #1257 Changed import of recursive annotations (#1068) from the format to a query level using the `import-annotation` parameter
* #1291 Added support for range queries such as `[[>AAA]] [[<AAD]]`
* #1293 Added `_ERRC` and `_ERRT` as pre-defined properties to aid error analysis
* #1299 Added dot named identifier restriction for subobject names containing a dot (`fooba.bar` reserved for extensions)
* #1313 Added usage count information to property pages
* #1321 Added [`$smwgSparqlRepositoryConnectorForcedHttpVersion`](https://semantic-mediawiki.org/wiki/Help:$smwgSparqlRepositoryConnectorForcedHttpVersion) setting to force a specific HTTP version in case of a #1306 cURL issue
* #1290 Added support for properties and prinrequests to be forwared to a redirect target if one exists
* #1329 Added positional preference for units when declared in `Corresponds to` (¥ 500 vs 500 JPY)
* #1350 Enlarged input field on special page "Browse"
* #1335 Added possibility to specify a display precision for a numeric datatype by either denoting a [`Display precision of`](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Display_precision_of) or using `-p<number of digits>` as `#ask` printout option
* #1344 Added `MonolingualTextValue` and `LanguageCodeValue`
* #1361 Added `--with-maintenance-log` option to `rebuildData.php`, `rebuildPropertyStatistics.php`, and `rebuildConceptCache.php`
* #1381 Added localizable context help for properties using the predefined property `Has property description` (which is specified as `MonolingualText` type)
* #1389 Added free date/time formatting support using the `-F[ ... ]` option
* #1391 Made subobject directly browsable from/in the Factbox
* #1396 Indicate `AC/CE` era for positive years if it was explicitly annotated
* #1397 Added support for microseconds in `DITime`
* #1401 Added support for parsing `年/月/日` date format in `DITime`
* #1407 Added quick result download links to `Special:Ask`
* #1410 Added support for `{{DISPLAYTITLE:title}}` caption using the [`Display title of`](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Display_title_of) property
* #1417 Added [`Allows pattern`](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Allows_pattern) property to define a value constraint using regular expressions and the required `smw-patternedit`right to add those expressions
* #1433 Added `--ignore-exceptions` and `exception-log` options to `rebuildData.php` while option `-v` is showing additional information about the processed entities
* #1440 Added various changes to accommodate MW 1.27
* #1463 Added support for the [`Has uniqueness constraint`](https://www.semantic-mediawiki.org/wiki/Help:Special_property_Has_uniqueness_constraint) property trait
* #1474 Added a search link for zero properties to the `Special:Properties`
* #1483 Added statistics about [outdated entities](https://www.semantic-mediawiki.org/wiki/Help:Outdated_entities) to the `Special:Statistics`
* #1513 `StringValueFormatter` to add `\n` on the first text element if it contains `*/#/:`
* #1545 Added `#LOCL` as `TimeValue` output format

## Bug fixes

* #541 Fixed duplicate column when "further results ..." are redirected to `Special:Ask`
* #753 Fixed number parsing of non-zero lead decimal numbers (.1 vs 0.1) / (T40476)
* #1244 Find redirect for a property when specified as a record field (in `PropertyListValue`)
* #1248 Fixed misplaced replacement of `_` in the `ImportValueParser`
* #1270 Fixed printout display of inverse properties
* #1272 Fixed serialization of `_rec` type in the `QueryResultSerializer`
* #1275 Fixed export of record type data when embedded in a subobject
* #1286 Fixed support for sorting by category
* #1287 Fixed exception for when `$smwgFixedProperties` contains property keys with spaces
* #1289 Fixed redirect statement for resources matched to an import vocabulary (`SPARQL` query)
* #1301 Fixed `count` query result discrepancy (to exclude redirect and deleted entities)
* #1314 Fixed hidden annotation copy of `[[ :: ]]` text values when embedded in query results
* #1318 Fixed possible `null` object in `AskParserFunction` when creating a `QueryProfile`
* #1357 Fixed `|+align=...` usage for `format=table`
* #1358 Fixed recognition of multi-byte boolean value
* #1348 Fixed single year detection in `TimeValue`
* #1414 Fixed exception caused by a missing message page on a `Service link` annotation
* #1449 Fixed mapping of imported URI to an internal `DataItem`
* #1450 Fixed export of concept
* #1453 Fixed off/on display in text value
* #1459 Fixed column display regression in `CategoryResultPrinter` for subobjects
* #1466 Fixed remote resource path detection that appeared in connection with a non-default extension setup
* #1473 Fixed exception caused by `ParameterInput` due to "HTML attribute value can not contain a list of values"
* #1477 Fixed query result from `SPARQLStore` to filter redirects natively
* #1489 Fixed fatal error in `RdfResultPrinter` due to namespace mismatch
* #1496 Fixed concept handling for `postgres`
* #1526 Fixed `_` handling for value strings submitted to the `Special:SearchByProperty`
* #1550 Fixed `SPARQLStore` `XML` response parsing for strings that contain UTF-8 characters

## Internal changes

* #1235 Improve query performance in `PropertyUsageListLookup`
* #1023 Split the `DocumentationParserFunction`
* #1264 Removed `pg_unescape_bytea` special handling for `postgres` in the `ResultPrinter`
* #1276 Extended `QueryResultSerializer` (relevant for the API output) to export the raw output of a time related value
* #1281 Extended `QueryResultSerializer` to export the internal property key
* #1291 Added `DescriptionProcessor` to isolate code path from the `SMWQueryParser`
* #1317 Switch to Sesame 2.8.7
* #1382 Added `DispatchingDataValueFormatter` and `ValueFormatterRegistry`
* #1385 Added `StringValueFormatter` and `CodeStringValueFormatter`
* #1388 Added `TimeValueFormatter`
* #1421 Added `DeferredDependencyLinksUpdater` to avoid violations reported by `TransactionProfiler` in MW 1.26+
* #1417 Added `PermissionPthValidator` together with new the `smwcurator` group and `smw-patternedit` right
* #1435 Added `DeferredCallableUpdate` (together with `$GLOBALS['smwgEnabledDeferredUpdate']`) to support MW's `DeferrableUpdate` interface (i.e. to support queuing DB related transactions)
* #1445 Added `userlang` as `ParserOutput` option
* #1451 Added `ExtraneousLanguage` interface
* #1460 Requires PHP extension mbstring in `composer.json`
* #1482 Added the `SMW::FileUpload::BeforeUpdate` hook
* #1512 Fixed test suite to support PHP7

## Contributors
