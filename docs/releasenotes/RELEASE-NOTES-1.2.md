# Semantic MediaWiki 1.2

Released on July 10, 2008.

See http://semantic-mediawiki.org/wiki/SMW_1.2

* New SMW storage backend (SMWSQLStore2)
  ** faster for queries and page display/rendering
  ** full equality support built-in, no performance impact
  ** support for disjunctions in queries (keyword "OR")
* vCard export for query results
* Improved semantic query syntax and processing
  ** shortcut query syntax #show for displaying properties of
     single pages, e.g. {{#show: Berlin | ?population}}
  ** property chains like [[property1.property2::value]]
  ** more detailed control of which query features to support
     (see setting $smwgQFeatures in SMW_Settings.php)
* Support for custom sortkey to control alphabetic sorting of
  all pages, using MediaWiki's {{DEFAULTSORTKEY: custom key}}
* Support for semantic interwiki links (e.g. [[property::meta:Test]])
* Stored queries on Concept: pages (concepts as "dynamic categories"),
  see http://semantic-mediawiki.org/wiki/Help:Concepts
* Automated updates: changes in templates and property definitions
  are automatically applied to affected pages (after some time)
* Extended maintenance scripts
  ** delete an existing (now unused) SMW store with SMW_setup --delete
  ** select SMW storage engine to use for scripts with option -b <Store>
  ** SMW_dumpRDF now supports restriction to concepts or concepts+categories
* SMW <1.0 features disabled by default (remove obsolete features),
  can be re-enabled with $smwgSMWBetaCompatible.
* Compatible with Semantic Forms 1.2.3 and MediaWiki 1.13 (current devel
  version)
