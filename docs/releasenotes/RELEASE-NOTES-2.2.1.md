# Semantic MediaWiki 2.2.1

Released on June 2nd, 2015.

## Bug fixes

* Fixed "Notice: Undefined variable: dataItem" in `QueryEngine`
* #1031 CategoryResultPrinter to recognize offset for further results
* #1033 Fixed assumption that always an array is sent to `JobBase` for booleans
* #1038 Fixed Fatal error: Call to undefined method `SMWDIError::getString`
* #1046 Fixed RuntimeException in `UndeclaredPropertyListLookup` when a DB prefix is used
* #1051 Fixed call to `DIWikiPage::getText` in `ConceptDescriptionInterpreter`
