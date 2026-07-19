# Semantic MediaWiki 1.1

* Support for formatted results on Special:Ask. "Further results" links
  from inline queries now preserve format.
* New iCalendar export for inline queries (format=icalendar)
* Query results can now be sorted by more than one property (just separate
  property names with "," in sort parameter)
* Initial support (beta) for synching external RDF stores with SMW.
  This also provides support for wiki-based SPARQL query services, see
  http://semantic-mediawiki.org/wiki/Help:SPARQL_endpoint
* More robust link generation code; even long query texts and links
  that contain very special characters are built properly.
* Extended translations. Completely new Arab translation.
* New SMW registry http://semantic-mediawiki.org/wiki/Special:SMWRegistry
  to replace hand-crafted list of "sites using SMW".
* Various bugfixes. For example:
 ** Enumerated properties (allows value) for Type:Page works now.
 ** Page moves are handled more reliably
