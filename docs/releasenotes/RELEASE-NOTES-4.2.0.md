# Semantic MediaWiki 4.2.0

Released on July 5th, 2024.

## Summary

This is a [minor release](../RELEASE-POLICY.md). Thus, it contains no breaking changes, only bug fixes and new features.

This release introduces the new faceted search feature, extends the API modules "ask" and "askargs," improves
documentation of Elasticsearch integration, and provides other fixes.

## Changes

* Added faceted searching, which provides users with a simple interface (special page "FacetedSearch") to quickly narrow
  down query results from a condition with the help of faceted views created from dependent properties and categories
  ([#5631](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5631))
* Added the "source" parameter to set a query source for queries using the API-modules "ask" and "askargs"
  ([#5633](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5633))
* Improved handling of logos ([#5635](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5635))
* Updated and improved documentation for Elasticsearch backend
* Fixed property linking for languages with fallback languages ([#5530](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5530), [#5638](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5638))
* Fixed footer sorting on table results ([#5617](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5617))
* Translation updates

## Contributors

Top Contributors

* Bertrand Gorge
* Niklas Laxström
* Mark A. Hershberger
* jaideraf
* alistair3149

Code Contributors

* James Hong Kong
* Bertrand Gorge
* Niklas Laxström
* Mark A. Hershberger
* jaideraf
* alistair3149
* Yvar
* Youri vd Bogert
* thomas-topway-it
* Robert Vogel
* Jeroen De Dauw
* Karsten Hoffmeyer

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 4.0.0, ensure the SMW version in `composer.json` is `^4.2.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`
* Step 3: run either MediaWiki's update.php or SemanticMediaWiki's
  [setupStore.php maintenance script](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_setupStore.php)

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory