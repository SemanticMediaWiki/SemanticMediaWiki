# Semantic MediaWiki 0.7

* New browsing interface for semantic data: Special:Browse
* Improved simple searching interfaces, making the old Special:Searchtriple
  obsolete by various new interlinked special pages.
* New formatting options for inline queries:
** Template-based formatting for formats "list" and "template"
** Transclusion of result articles with format "embedded"
** Counting query results with format count.
* New datatype for enumerated string values (Type:Enum).
* Pages of attributes and relations now list all uses of these properties.
* Pages of types now list all attributes using a type.
* New Special:WantedRelations showing relations that are used but have no page.
* Improved support for arbitrary symbols in string values, including wiki links
  and HTML entities (now correct in RDF).
* Improved headers for query tables, with sort icon and link to attribute/relation
  separated.
* Added maintenance script to rebuild semantic data, thus fixing any inconsistencies
  in the semantic database that may have occurred earlier or due to text-only imports
  of pages.
* Translations to further languages, including Hebrew (right-to-left).
* New cleaner storage implementation, allowing to run MediaWiki parsertests with the
  option $smwgDefaultStore = SMW_STORE_TESTING; in LocalSettings.php.
* MediaWiki-1.10-Ready ;-)
* Simplified installation (no more manual patching with MediaWiki 1.10).
* Many bugfixes.
