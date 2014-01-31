# Semantic MediaWiki 1.9.0.3

### Bug fixes

* #80  Fixed issue with running the system tests on a MySQL based setup
* #110 Fixed type mismatch issue in the cahcing code of SQLStore3
* #121 (Bug 60336) Fix sortkey issue for multibyte characters in ListResultPrinter
* #145 Fixed PHP strict standards notice in SMWParamFormat::formatValue
* #146 Fixed 1.9.0.2 regression in resource paths that caused the SMW badge and JS+CSS to not be loaded on some wikis

### Internal enhancements

* #118 Add a possibility to inject a Revision into the ContentParser
* #119 Add a LinksUpdate integration test
* #131 The Query Duration special propertyis now internationalized
* #132 Added smoke test for the special language files
* #142 Changed the language code for Norwegian (Bokmål variant) from "no" to "nb" for L10n for datatypes, special properties, etc.
