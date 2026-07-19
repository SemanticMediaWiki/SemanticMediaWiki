# Semantic MediaWiki 0.6

Released on November 18, 2006.

* New Special:Ask for directly browsing query results and for testing queries.
* New output format "timeline" for inline queries that deal with dates. Available
  parameters are: timelinestart (name of start date attribute), timelineend (name
  of end date attribute, if any), tiemlinesize (CSS-encoded height), timelinebands
  (comma-separated list of bands such as DAY, WEEK, MONTH, YEAR, ...), and
  timelineposition (one of start, end, today, middle).
* Complete RDF export is now possible with a maintenance script, which can e.g. be
  run periodically on a server to create RDF files.
* New "service links" feature: any attribute can provide configurable links to
  online services. As a special case, the map-services of geo-coordinates are now
  fully configurable.
* Inline queries now link to life search for further results if not all results
  were shown inline.
* The formatting code for inline queries was rewritten to become more powerful.
  For instance, multi-property outputs in list format will never produce empty
  parentheses now.
* RDF-export code is cleaner and some further OWL DL incompatibilities are caught.
* RDF-export now can generate browsable RDF (with backlinks) even for Category
  pages.
* Improved headers for sorting tables. Sort icon now visible even if no text is
  shown in header.
* Many bugfixes.
