# Semantic MediaWiki 0.5

* Customised datatypes for unit conversion: it is now possible to create customised
  linear unit conversions by appropriate statements on type articles. This also
  enables full localisation of all units of measurement.
* Customized display of units: every attribute can now decide which units to display
  in factbox and query results. Internally, values are still normalised, but users
  can adjust the view to the most common description of some attribute.
* Support for importing vocabularies from external ontologies. For instance, elements
  of the wiki can now be mapped to the FOAF ontology during export. The import is
  controlled by whitelist-like message articles.
* New attribute datatypes for URLs and URIs, some of which can be exported in RDF as
  ObjectProperties. A blacklist is used to prevent technically problematic URIs from
  being used there (e.g. most don't want to use OWL language elements as data).
* New attribute datatype for temperature, since this cannot be defined by a linear
  custom unit conversion.
* Improved Special:Relations and Special:Attributes, including a quicklink to searching
  occurrences of some annotation.
* Unit support for inline queries. Desired output unit can be adjusted through query.
* Improved code layout, using object-orientation features of PHP5.
* Many bugfixes.
