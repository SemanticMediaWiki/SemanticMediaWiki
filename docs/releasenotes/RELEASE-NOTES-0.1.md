# Semantic MediaWiki 0.1

Released on September 30, 2005.

Changes by mak (0.1b, 1 Dec 2005):
* Reworked internal data representation. All information now is
  properly encoded in URIs and decoded for display. This is an
  important prerequisite for storing attributes and auxilliary
  triples, which otherwise could not be distinguished from the
  relational information.
* New Special SearchTriple to replace the current SearchSemantic,
  which is currently only half functional since it believes that
  the database contains only simple names for articles, but not
  full URIs.
* Minor adjustments in handling of namespaces: namespaced aritcles
  now properly work as subjects and are displayed with namespace in
  in the infobox.

Changes by mak (0.1b, 19 Nov 2005):

* New type management; attributes can now be declared by creating
  relations of type "has type" inside their articles (Attribute:X).
  Possible targets are the builtin types (Type:String, Type:Geographic
  length, etc.).
* New internal method SMWGetTriples for directly retrieving triples
  from the storage. Accepts subject, predicate, object pairs, where
  any two can be left out.
* Improved layout for infoboxes.

Changes by mak (0.1b, 13 Nov 2005):

* added support for separator "," in data numbers,
* added tooltips for unit conversion.

Changes by mak (0.1b, 17 Oct 2005):

* added attribute support [[attribute name:=value|alternative text]];
  currently, parsing these within the article works, including an
  info box at the bottom; however, assignment from attributes to
  datatypes is still hardcoded and attribute-annotations are neither
  stored as triples nor are they supported in search,
* added basic type support for STRING, INTEGER, and FLOAT,
* added framework for unit conversion and first unit support: unit
  conversion is achieved by callback functions, so that adding types
  for new units boils down to writing a single unit conversion function,
* code split into several files for easier colaboration of developers,
* new naming convention "SMW"-prefix for all top level code elements of
  the extension,
* moved main storage methods to SMW_Storage.php, this should simplify
  the conversion to another storage backend (triplestore),
* moved stripping of semantic relations to SMW_Stripsemantics.php;
  if this feature is desired, this file needs to be updated slightly
  (also to include semantic attributes) and its methods connected to
  their appropriate hooks as done in 0.1

Changes by mak (0.1a, 4 Oct 2005):

* moved parsing process to ParserAfterStrip to support <nowiki>; it
  has to be done even later to support template inclusion properly,
* changed process of storing/retrieving: no more stripping of semantic
  relations before saving -- the annotations now appear exactly where
  the user has put them, keeping them easier to read and maintain,
* parse only once: saving is based on the relations that were retrieved
  during the earlier call of parse(); for this to work, saving needs to
  be deferred -- it is currently done at ArticleSaveComplete [should
  there be a dedicated hook for deferred saving?],
* enabled removal of semantic links on article deletion,
* changed layout of semantic links factsheet, including some neat grouping
  feature.

This is a pre-alpha version of the Semantic MediaWiki extensions.
It includes:

* support for typed links [[link type::link target|link label]],
* rendering of fact sheet on semantic relations at article bottom,
* Special:SearchSemantic (alpha), featuring autocompletion for
  link types.
