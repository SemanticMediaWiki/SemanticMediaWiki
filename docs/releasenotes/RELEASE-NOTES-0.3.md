# Semantic MediaWiki 0.3

Changes by mak (0.3, 06 Apr 2006):

* Compatibility updates for MediaWiki 1.6

Changes by denny/mak (0.3, 25 Mar 2006):

* Internal: improved management of special properties
* RDF export: OWL conformant export of all available content data, including category information
* RDF export: recursive export, "streaming"
* UI: further internationalization, internationalized float number format (decimal separator)
* UI: new infobox section for recognized special properties
* new Specials to show all relations/attibutes
* new experimental Special to import data from existing OWL/RDF ontologies
* new special property "equivalent URI" that allows to map wiki concepts to URIs in other ontologies

Changes by kai/mak (0.3preview, 15 Mar 2006):

* Internal: new internal storage management; cleaner, more flexible, and more efficient
* Internal: new internal type registration API
* Internal: new internal management for special properties (e.g. 'has type')
* Internationalization: almost complete; namespaces, special properties (e.g. 'has type'), datatype labels
* RDF export: support for multiple mimetypes (rdf+xml and xml); needed for Piggybank
* RDF export: support for bulk export
* RDF export: XSD datatypes and correct instance classification (rdf:type)
* UI: extended Special:SMWAdmin to convert data from old internal datatable to new format
* UI: duplicate attribute values eliminated in infobox
* UI: types can switch off quicksearch links
* UI: more human-oriented error mesages ;-)
* UI: service links for infobox and search
* new datatype for geographic coordinates, accepting many kinds of coordinate inputs, and providing links to standard mapsources
* Simple semantic search supports imprecise search again
