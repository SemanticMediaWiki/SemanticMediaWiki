# Semantic MediaWiki 1.5.5

* Support for Turtle syntax (e.g. use "syntax=turtle" as a parameter to 
  when calling Special:ExportRDF.
* New query format "rdf" to export query results to RDF; the "syntax" 
  parameter can be set to "rdfxml" or "turtle" to specify a syntax.
* Fixed several bugs, including whitespace problems in queries using the
  list format, an issue with large offset values and DatatypeProperties
  being declared as ObjectProperties.
* More modular export code (split one file into 3), simplifies 
  manipulation and maintenance.
