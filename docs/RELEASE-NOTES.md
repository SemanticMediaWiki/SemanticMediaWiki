# Semantic MediaWiki 1.9.0.3

### Bug fixes

* #80  Fixed issue with running the system tests on a MySQL based setup
* #110 Fixed type mismatch issue in the cahcing code of SQLStore3
* #121 (Bug 60336) Fix sortkey issue for multibyte characters in ListResultPrinter

### Internal enhancements

* #118 Add a possibility to inject a Revision into the ContentParser
* #119 Add a LinksUpdate integration test
* #131 The Query Duration special propertyis now internationalized
* #132 Added smoke test for the special language files
* #142 Change language code for Norwegian (Bokmål variant) from "no" to "nb" for L10n for datatypes, special properties, etc.
