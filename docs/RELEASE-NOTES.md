# Semantic MediaWiki 1.9.0.3

### New features
* #162 Possibility to create `_MEDIA` and `_MIME` property annotation when uploading a file (this feature can only be used with appropriate  [$smwgPageSpecialProperties](https://www.semantic-mediawiki.org/wiki/Help:$smwgPageSpecialProperties) settings and the run of the update.php script)

### Bug fixes

* #80  Fixed issue with running the system tests on a MySQL based setup
* #110 Fixed type mismatch issue in the cahcing code of SQLStore3
* #121 (Bug 60336) Fixed sortkey issue for multibyte characters in ListResultPrinter
* #144 (Bug 60284) Fixed record data type issue when using #set/#subobject
* #145 Fixed PHP strict standards notice in SMWParamFormat::formatValue
* #146 Fixed 1.9.0.2 regression in resource paths that caused the SMW badge and JS+CSS to not be loaded on some wikis
* #148 Fixed regresion that made data type labels case sensitive
* #151 (Bug 50155) Fixed issue with category hierarchies on SQLite
* #164 (Bug 19487) Fixed update of predefined properties when uploading a file

### Internal enhancements

* #112 Added a date data type regression test
* #118 Added a possibility to inject a Revision into the ContentParser
* #119 Added a LinksUpdate integration test
* #131 The Query Duration special propertyis now internationalized
* #132 Added smoke test for the special language files
* #142 Changed the Norwegian language code (Bokmål variant) from "no" to "nb" for L10n for datatypes, special properties, etc.
* #144 Added a record data type regression test
* #151 Added a category hierarchy regression test
