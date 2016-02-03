# Semantic MediaWiki 2.4

Not a release yet.

## Highlights

* #1329
* #1335
* #1344 (#1381)
* #1389

## New features and enhancements

* #498 Extended `rebuildData.php` to remove outdated enitity references (see `PropertyTableOutdatedReferenceDisposer`)
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
* #1381 Added localizable context help for properties using the predefined property `Has property description` (which is specified as `MonolingualText` type)
* #1389 Added free date/time formatting support using the `-F[ ... ]` option
* #1391 Made subobject directly browsable from/in the Factbox

## Bug fixes

* #1244 Find redirect for a property when specified as a record field (in `PropertyListValue`)
* #1248 Fixed misplaced replacement of `_` in the `ImportValueParser`
* #1270 Fixed printout display of inverse properties
* #1272 Fixed serialization of `_rec` type datavalues in the `QueryResultSerializer`
* #1275 Fixed export of record type data when embedded in a subobject
* #1286 Fixed support for sorting by category
* #1287 Fixed exception for when `$smwgFixedProperties` contains property keys with spaces
* #1289 Suppresses redirect statement in sparql query for resources matched to an import vocabulary
* #1314 Fixed hidden annotation copy of `[[ :: ]]` text values when embedded in query results
* #753 (T40476) Fixed number parsing of non-zero lead decimal numbers (.1 vs 0.1)
* #1357 Fixed `|+align=...` usage for `format=table`
* #1358 Fixed recognition of multi-byte boolean value

## Internal changes

* #1235 Improve query performance in `PropertyUsageListLookup`
* #1023 Split the `DocumentationParserFunction`
* #1264 Removed `pg_unescape_bytea` special handling for postgres in the `ResultPrinter`
* #1276 Extended `QueryResultSerializer` (relevant for the API output) to export the raw output of a time related value
* #1281 Extended `QueryResultSerializer` to export the internal property key 
* #1291 Added `DescriptionProcessor` to isolate code path from the `SMWQueryParser`
* #1317 Switch to Sesame 2.8.7
* #1382 Added `DispatchingDataValueFormatter` and `ValueFormatterRegistry`
* #1385 Added `StringValueFormatter` and `CodeStringValueFormatter`
* #1388 Added `TimeValueFormatter`

## Contributors
