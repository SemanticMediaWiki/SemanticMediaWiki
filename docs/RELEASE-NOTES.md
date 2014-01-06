# Semantic MediaWiki 1.9.0.1

### New features

* (Issue 82) It is now possible to view all SMW settings via Special:SMWAdmin.

### Bug fixes

* (Bug 59204) Fixed removal of properties on deletion of pages in custom namespaces.
* (Issue 73) Fixed double table prefixing issue causing problems when using SQLite.
* (Issue 84) The link to the INSTALL file on Special:SMWAmin has been fixed.

### New configuration parameters

* [$smwgOnDeleteAction](https://semantic-mediawiki.org/wiki/Help:$smwgOnDeleteAction) (incl. $smwgDeleteSubjectAsDeferredJob, $smwgDeleteSubjectWithAssociatesRefresh)
