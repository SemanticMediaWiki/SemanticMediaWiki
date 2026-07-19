# Semantic MediaWiki 1.4.3

Released on August 15, 2009.

See http://semantic-mediawiki.org/wiki/SMW_1.4.3

* A new query format, 'category', that displays values in the style of a
  MediaWiki category page
* A new 'columns' parameter for the 'ol' and 'ul' formats
* The 'csv' format now prints a header row by default
* The "::~" comparator in queries can now also be used for properties of type
  Geographical coordinate, to find points within a certain distance
* Using "-" as an output format for query printouts, or leaving the formatting
  string empty now leads to printout values being returned as plain, unformatted
  values. Recall that the general format for printouts in #ask is as follows:
   ?propertyname # format = label
* New special property "Has improper value for" can be used to track input errors
  (links pages where error happened to properties for which improper data was given).
* New configuration parameter $smwgMaxNonExpNumber to set the maximal number that
  SMW will normally display without using scientific exp notation. Defaults to
  1000000000000000.
* New configuration parameter $smwgMaxPropertyValues to control number of values
  that are shown for each page in the listing on Property pages. Defaults to 3.
* Now a set of stores can be added, e.g. virtual stores, and then the new #ask
  parameter "source" is used to rout to a specific store.
* Labels for inverse properties can be used to improve the output of the Browse
  special page.
* The #ask parameter "headers" can now be given the value "plain" to get query
  results that show the printout label (e.g. property name) but without a link.
* Special:PageProperty now shows all values of a property when omitting the subject.
* Numerous bugfixes
