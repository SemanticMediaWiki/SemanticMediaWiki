# Semantic MediaWiki 2.4

Not a release yet.

## New features and enhancements

* #498 Extended `rebuildData.php` to remove outdated enitity references (see `PropertyTableOutdatedReferenceDisposer`)
* #1243 Make failed queries discoverable
* #1246 Added support for `~`/`!~` on single value queries
* #1267 Added the `browseByProperty` API module to fetch a property list or individual properties via the WebAPI
* #1268 Restored compliance with MediaWiki's 1.26/1.27 WebAPI interface to ensure continued support for the `ask` and `askargs` output serialization
* #1257 Changed import of recursive annotations (#1068) from the format to a query level using the `import-annotation` parameter

## Bug fixes

* #1244 Find redirect for a property when specified as a record field (in `PropertyListValue`)
* #1248 Fixed misplaced replacement of `_` in the `ImportValueParser`
* #1270 Fixed printout display of inverse properties
* #1272 Fixed serialization of `_rec` type datavalues in the `QueryResultSerializer`

## Internal changes

* #1235 Improve query performance in `PropertyUsageListLookup`
* #1023 Split the `DocumentationParserFunction`
* #1264 Removed `pg_unescape_bytea` special handling for postgres in the `ResultPrinter` 

## Contributors
