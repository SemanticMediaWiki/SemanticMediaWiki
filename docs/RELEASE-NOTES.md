# Semantic MediaWiki 2.4

Not a release yet.

## New features and enhancements

* #498 Extended `rebuildData.php` to remove outdated enitity references (see `PropertyTableOutdatedReferenceDisposer`)
* #1243 Make failed queries discoverable
* #1246 Added support for `~`/`!~` on single value queries

## Bug fixes

* #1244 Find redirect for a propertiy specified as a record field (in `PropertyListValue`)
* #1248 Fixed misplaced replacment of `_` in `ImportValueParser`

## Internal changes

* #1235 Improve query performance in `PropertyUsageListLookup`
* #1023 Split the `DocumentationParserFunction`

## Contributors
