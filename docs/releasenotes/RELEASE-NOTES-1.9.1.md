# Semantic MediaWiki 1.9.1

Released February 9th, 2014.

### New features

* #162 Added possibility to create `_MEDIA` [(special property "Media type")](https://semantic-mediawiki.org/wiki/Help:Special_property_Media_type) and `_MIME`
[(special property "MIME type")](https://semantic-mediawiki.org/wiki/Help:Special_property_MIME_type)
property annotation when uploading a file (This feature can only be used with appropriate
[`$smwgPageSpecialProperties`](https://www.semantic-mediawiki.org/wiki/Help:$smwgPageSpecialProperties)
settings after running the "update.php" script.)
* #173 Extended the [factbox](https://semantic-mediawiki.org/wiki/Factbox#The_factbox) in
order to display "historical" data when used by `action=history`
* #180 Added further CSS classes for improved customization of special page
["Ask"](https://semantic-mediawiki.org/wiki/Help:Special:Ask)

### Bug fixes

* #80  Fixed issue with running the system tests on a MySQL based setup
* #110 Fixed type mismatch issue in the caching code of SQLStore3
* #121 (Bug 60336) Fixed sortkey issue for multibyte characters in ListResultPrinter
* #144 (Bug 60284) Fixed record data type issue when using #set/#subobject
* #145 Fixed PHP strict standards notice in SMWParamFormat::formatValue
* #146 Fixed 1.9.0.2 regression in resource paths that caused the SMW badge and JS+CSS to
not be loaded on some wikis
* #148 Fixed regression that made data type labels case sensitive
* #151 (Bug 50155) Fixed issue with category hierarchies on SQLite
* #164 (Bug 19487) Fixed update of predefined properties when uploading a file
* #166 Fixed Factbox display issue during preview and edit mode
* #170 Fixed 1.9.0 regression of [special property "Is a new page"](https://semantic-mediawiki.org/wiki/Help:Special_property_Is_a_new_page) (`_NEWP`)
(Run ["SMW_refreshData.php"](https://semantic-mediawiki.org/wiki/Help:SMW_refreshData.php)
to amend falsely set values.)

### Internal enhancements

* #112 Added a date data type regression test
* #118 Added a possibility to inject a Revision into the ContentParser
* #119 Added a LinksUpdate integration test
* #131 Added internationalization support for special property "Query duration"
* #132 Added smoke test for the special language files
* #142 Changed the Norwegian language code (Bokmål variant) from "no" to "nb" for
L10n of datatypes, special properties, etc.
* #144 Added a record data type regression test
* #151 Added a category hierarchy regression test
