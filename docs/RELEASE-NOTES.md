# Semantic MediaWiki 2.3

This is not a release yet.

## New features

* #1001 Added `$GLOBALS['smwgSparqlQFeatures']` with option `SMW_SPARQL_QF_REDI` to support property/value redirects in queries (can only be used in connection with a SPARQL 1.1 supported repository)
* #1003 Added option `SMW_SPARQL_QF_SUBP` to enable subproperty hierarchy support for the `SPARQLStore` (with the same requirement as in #1001)
* #1012 Added option `SMW_SPARQL_QF_SUBC` to enable subcategory hierarchy support for the `SPARQLStore` (with the same requirement as in #1001)
* 

## Enhancements

* #1042 Extended `rebuildData.php` to inform about the estimated % progress
* #1047 Extended context help displayed on `Special:Types` and subsequent type pages
* #1049 Added resource targets to allow MobileFrontend to load SMW related modules
* #1053 Added a `CSS` class to visually distinguish a "normal" from a subobject link
* #1063 Added `$GLOBALS['smwgValueLookupCacheType']` to improve DB lookup performance though the use of a responsive cache layer (such as `redis`) and buffer repeated requests either from the API or page view to the backend.
* #1066 Extended `InTextAnnotationParser` to correctly handle `::` or `:::` and allow for proper processing of annotations such as `[[DOI::10.1002/123::abc]]` or `[[Foo:::123]]`

## Bug fixes

* #682 Fixed id mismatch in `SQLStore`
* #1005 Fixed syntax error in `SQLStore`(`sqlite`) for temporary tables when a disjuntive category/subcategory query is executed
* #1033 Fixed PHP notice in `JobBase` that was based on an assumption that parameters are always an array
* #1038 Fixed Fatal error: Call to undefined method `SMWDIError::getString`
* #1046 Fixed RuntimeException in `UndeclaredPropertyListLookup` for when a DB prefix is used
* #1051 Fixed call to undefined method in `ConceptDescriptionInterpreter`
* #1054 Fixed behaviour for `#REDIRECT` to create the same data reference as `Special:MovePage`
* #1059 Fixed usage of `[[Has page::~*a*||~*A*]]` for `SPARQLStore` when `Has page` is declared as page type 
* #1060 Fixed usage of `(a OR b) AND (c OR d)` as query pattern for the `SQLStore`
* #1067 Fixed return value of the `#set` parser
* #1074 Fixed duplicated error message for a `_dat` DataValue
* #1081 Fixed mismatch of `owl:Class` for categories when used in connection with a vocabulary import

## Internal changes

* #1018 Added `PropertyTableRowDiffer` to isolate code responsible for computing data diffs (relates to #682)
* #1039 Added `SemanticData::getLastModified`
* #1041 Added `ByIdDataRebuildDispatcher` to isolate `SMWSQLStore3SetupHandlers::refreshData`
* #1071 Added `SMW::SQLStore::AddCustomFixedPropertyTables` hook to simplify registration of fixed property tables by extension developers
