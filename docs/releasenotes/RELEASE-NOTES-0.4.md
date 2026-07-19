# Semantic MediaWiki 0.4

Released on May 12, 2006.

Semantic MediaWiki 0.4 includes the following new features:

* Support for inline queries: it is now possible to <ask> queries in
  articles, the answers of which are included into the displayed page.
  Conjunctions and nesting of queries is supported. Datatype queries
  for values above or below some threshold are possible. Outputs can be
  displayed in many different formats, including bulleted and numbered
  lists, tables with intercative (JScript) sorting (credits go to
  Stuart Langridge for www.kryogenix.org/code/browser/sorttable/), and
  plain text. See http://semantic-mediawiki.org/wiki/Help:Inline_queries
  for documentation.
* Improved output for Special:Relations and Special:Attributes: usage of
  relations and attributes is now counted
* Improved ontology import feature, allowing to import ontologies and to
  update existing pages with new ontological information
* Experimental support for date/time datatype
* More datatypes with units: mass and time duration
* Support for EXP-notation with numbers, as e.g. 2.345e13. Improved number
  formatting in infobox.
* Configurable infobox: infobox can be hidden if empty, or switched off
  completely. This also works around a bug with MediaWiki galeries.
* Prototype version of Special:Types, showing all available datatypes with
  their names in the current language setting.
* "[[:located in::Paris]]" will now be rendered as "located in [[Paris]]"
* More efficient storage: changed database layout, indexes for fast search
* Code cleaned up, new style guidelines
* Bugfixes, bugfixes, and some more bugfixes

Semantic MediaWiki 0.4 has not been tested on MediaWiki below 1.6.1 and might
fail to operate correctly in this case. Some functions explicitly use code
that was introduced in 1.6.
