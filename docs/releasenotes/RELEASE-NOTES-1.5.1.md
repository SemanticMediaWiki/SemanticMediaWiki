# Semantic MediaWiki 1.5.1

* Added query comparator "!~" that works like the negation of "~", i.e. that
  retrieves exactly those values to which the provided pattern (with * and ?)
  does not match.
* Updated OWL/RDF export to include more data (modification dates, and page
  namespaces). Changed export ontology to no longer use OWL AnnoationProperties
  since OWL 2 DL supports property values also for classes/properties.
* Improvements in code style and structure, following general MediaWiki
  conventions. Most notably, the file to include for loading SMW now is
  SemanticMediaWiki.php in the base directory (including the old Settings.php
  will still work).
* Preparation of future "SMWLight" release for wikis that want to enable users
  to add property values to pages, but which do not want to include complex
  query features.
* Added a nmber of hooks to improve compatibility with extensions.
* Various bugfixes, and translation updates.
