# Semantic MediaWiki 1.0

## Changes in SMW1.0 as compared to SMW0.7

* Simplified semantic annotations: just one kind of annotation ("Property").
* Significant speedup (both server and network load substantially reduced,
  faster RDF export, more efficient query result formatting).
* Prettier and easier to understand interfaces:
  ** New tooltips for warnings and additional information.
  ** Simplified factbox layout, with all properties in alphabetic order.
  ** Inline warnings to simplify trouble shooting with annotations.
  ** Improved, more helpful and informative warning and error messages.
  ** Highlighting for built-in elements. E.g. built-in types are visually
     distinguished from arbitrary types; useful as visual feedback.
  ** Error/warning reporting for (inline) queries.
* More powerful output formatting for semantic querying:
  ** new {{#ask:...}} parser function syntax for inline queries, fully
     compatible with MediaWiki templates, template parameters, and parser
     functions of other extension
  ** more readable inline query structure in #ask parser function,
     printouts separated from query
  ** semantic RSS feeds making feeds from query results via "format=rss"
  ** new printout format "?Category:Name" for #ask
  ** option to hide main column by setting "mainlabel=-", and reinserting
     via print request "?" (only for #ask)
* More expressivity for semantic querying:
  ** support for subproperties,
  ** improved equality resolution (redirects),
  ** support for disjunctions,
  ** inequality check for datavalues ("[[property::!value]]")
  ** optional pattern matching for string values ("[[property::~Semant*]]")
  ** automatic sorting on sort-parameter (no additional condition needed)
* New/improved datatypes:
  ** Type:Page for explicitly specifying properties that are "relations"
  ** better media support in Type:Page: special treatment of Image: and Media:
  ** Type:Number as universal replacement for Type:Integer and Type:Float
  ** Type:URL as universal replacement for old Type:URL and Type:URI
  ** Type:Geographic coordinates completely rewritten. More input formats
     supported, more liberal parsing to accept most inputs
  ** special property "allows value" works for all types
  ** display units are now easier to select via property "display units"
  ** Improved data display: URL-links and tooltips work for queries results
     and on special pages
* Improved special pages:
  ** simpler interface for Special:Ask, hide query when using "further results" link
  ** hints and warning for property usage/declaration in Special:Properties
  ** extra information and warnings for types on Special:Types
  ** Special:SemanticStatistics as faster replacement for earlier "ExtendedStatistics"
* Better internationalisation:
  ** updates in all translation files
  ** new translations to Dutch, Chinese (tw/ch), Korean (beta)
  ** alias strings for all SMW elements; English labels are allowed in all
     languages, names of old SMW elements still work as aliases for their
     replacements.
* New experimental n-ary properties, allowing property values to consist of
  a list of entries.
* Ontology import re-enabled (simple annotation import)
* Maintenance script SMW_refreshData now can rebuild all SMW data structures, fixing
  even exotic database problems on most sites.
* New maintenance script for announcing site to Semantic Web crawlers.
* Support for upcoming MediaWiki 1.12
* Improved APIs and various new hooks to simplify the life of SMW extension developers.
* Many bugfixes.

Other changes for SMW1.0 include:
* Type:Enum became obsolete, since all types now suppport "allows value", but it
  remains an alias for Type:String.
* Some configuration options for LocalSettings.php have changed. Read INSTALL
  for details on how to upgrade from your old installation.

## Semantic MediaWiki post 1.0RC3

* Support for dynamic, query-generated RSS-feeds via query format "rss".
* Optional query feature for pattern matching in Type:String property values.
* Correct dynamic sorting of result tables, even for dates and numerical values.
* Thumbnail images when displaying property values from Image namespace.
* Simplified use of "sort" parameter in queries.
* Support for upcoming MediaWiki 1.12 (major parser changes).
* More efficient link generation in query results. Link all query results by
  default now.
* Maintenance script SMW_refreshData now can rebuild all SMW data structures,
  fixing even exotic database problems on most sites.
* New maintenance script for announcing site to Semantic Web crawlers.
* Various bugfixes.

## Semantic MediaWiki 1.0RC3

* New method for integrating inline queries via #ask parser function, separation of
  query and printout requests, full compatibility with templates.
* New layout for Special:Ask to reflect #ask structure.
* New printout option: "?Category:Name" to ask for membership in that category.
* Re-enabled service links (e.g. use [[provides service::online maps]] on any page of
  a property to Type:Geographic coordinates).
* Re-enabled Type:Boolean.
* Prototype translation for Korean (still alpha).
* Various minor bugfixes.

## Semantic MediaWiki 1.0RC2

* Experimental Postgres support.
* More liberal parsing for geographic coordinates, most user inputs accepted now.
* Improved URL datatype: better linking behavior, tolerant towards Unicode-URLs.
* Significantly improved performance for RDF export.
* Complete translations for Fr, Zh-tw, and Zh-ch added.
* Various minor bugfixes.

## Semantic MediaWiki 1.0RC1

* Simplified semantic annotations: just one kind of annotation ("Property").
* Significant speedup (both server and network load substantially reduced).
* Prettier and easier to understand interfaces:
  ** New tooltips that work on both normal and special pages.
  ** Simplified factbox layout, with all properties in alphabetic order.
  ** Inline warnings to simplify trouble shooting with annotations.
  ** Improved, more helpful and informative warning and error messages.
  ** Highlighting for built-in elements. E.g. built-in types are visually
     distinguished from arbitrary types; useful as visual feedback.
  ** Error/warning reporting for (inline) queries.
* Alias strings for all SMW elements. English labels are allowed in all
  languages, names of old SMW elements still work as aliases for their
  replacements.
* More expressivity for semantic querying:
  ** support for subproperties,
  ** improved equality resolution (redirects),
  ** support for disjunctions,
  ** inequality check for datavalues ("[[property::!value]]")
* New/improved datatypes:
  ** Type:Page for explicitly specifying properties that are "relations"
  ** Type:Number as universal replacement for Type:Integer and Type:Float
  ** Type:URL as universal replacement for old Type:URL and Type:URI
  ** Type:Geographic coordinates completely rewritten. More input formats
     supported now (e.g. coordinates without "," separating Lat and Long)
  ** special property "allows value" works for all types
  ** display units are now easier to select via property "display units"
  ** Improved data display: linked URLs and tooltips work for queries and
     special pages
* Improved maintenance special pages:
  ** Hints and warning for property usage/declaration in Special:Properties
  ** Extra information and warnings for types on Special:Types
  ** Special:SemanticStatistics as faster replacement for earlier "ExtendedStatistics"
* New experimental n-ary properties, allowing property values to consist of
  a list of entries.
* Ontology import re-enabled (simple annotation import)
* Dutch translation added (by Siebrand Mazeland)
* Improved APIs and various new hooks to simplify the life of SMW extension developers.
* Many bugfixes.

Other changes for the RC1 include:
* No more support for Type:Boolean. Will be re-enabled later.
* Type:Enum became obsolete, since all types now suppport "allows value", but it
  remains an alias for Type:String.
* Service links are not working in this Release Candidate yet, especially coordinate
  values do not link to maps yet. This will reappear before SMW1.0 final.
