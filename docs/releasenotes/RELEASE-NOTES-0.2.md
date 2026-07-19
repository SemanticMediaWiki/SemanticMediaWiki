# Semantic MediaWiki 0.2

Changes by mak (0.2c, 9 Mar 2006):

* added basic language support functionality
* improved installation process (SMW_LocalSettings.php, simpler patching for Setup.php)

Changes by mak (0.2c, 1 Mar 2006):

* RDF Export enabled
* more CSS and an icon to show RDF download link on pages

Changes by kai/mak (0.2c, Feb 2006):

* Added new custom stylesheet and JScript (kai/mak)
* New JScript tooltips (kai)
* New style for infobox search items (mak)

Changes by mak (0.2c, Feb 2006):

* Added new Special:SMWAdmin that allows relatively painless upgrade
  from versions <=0.2 where no namespaces were used.
* Added support for moving pages with its stored triples.
* BUGFIX: triples in articles with SQL-hostile symbols (e.g. ') are
  now working.

Changes by mak (0.2b, Jan 2006):
* Changed directory structure for more clarity, easier installation
  and upgrade.
* Now using custom namespaces for Relations, Attributes, and Types,
  and their talks.
* Semantic features can be switched on or off for each namespace
  individually.
* Registered extension for MediaWiki's "Special:Version".
* BUGFIX: Configuration now takes fixed servername to use in storing
  URIs. Before, different access methods (e.g. direct IP vs. servername)
  generated different URIs.
* BUGFIX: Attributes that could not be parsed now do not generate
  triples with empty object in our database.

Changes by mak (0.2a, 4 Dec 2005):
* Attribute values are now correctly stored and retrieved.
* Special SearchTriple greatly enhanced, such that queries for
  attributes become possible (including unit conversion).
* Links from attributes in infobox to the new search form.
* Major code cleanup: SMW_AttributeStore.php now is called
  SMW_SemanticData.php and managemes all types of semantic
  data, including printout and storage. SMW_Hooks.php was freed
  of all code with similar purpose. Look-up of attribute types
  was moved from SemanticData to Datatype.
