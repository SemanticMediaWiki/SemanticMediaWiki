# Semantic MediaWiki 2.3

This is not a release yet.

## New features

* #1001 Added `$GLOBALS['smwgSparqlQFeatures']` with option `SMW_SPARQL_QF_REDI` to support property/value redirects in queries (can only be used in connection with a SPARQL 1.1 supported repository)
* #1003 Added option `SMW_SPARQL_QF_SUBP` to enable subproperty hierarchy support for the `SPARQLStore` (with the same requirement as in #1001)
* #1012 Added option `SMW_SPARQL_QF_SUBC` to enable subcategory hierarchy support for the `SPARQLStore` (with the same requirement as in #1001)
* 
## Enhancements

*

## Bug fixes

* #682 Fixed id mismatch in `SQLStore`
* #1005 Fixed syntax error in `SQLStore`(`sqlite`) for temporary tables when a disjuntive category/subcategory query is executed
* #1033 Fixed PHP notice in `JobBase` that was based on an assumption that parameters are always an array
* #1038 Fixed Fatal error: Call to undefined method `SMWDIError::getString`

## Internal changes

* #1018 Added `PropertyTableRowDiffer` to isolate code responsible for computing data diffs (relates to #682)
* #1039 Added `SemanticData::getLastModified`
