# Semantic MediaWiki 4.2.0

Released on TBD, 2023.

## Summary

This is a [minor release](../RELEASE-POLICY.md). Thus it contains no breaking changes, only bug fixes and new features.

TODO: summary

## Breaking changes
* 

## Changes

* Add Special:FacetedSearch; this special page provides users with a simple interface to quickly narrow down query results from a condition with the help of faceted views created from dependent properties and categories
* Allow the use of the "source" parameter to set a query source for queries using the API-modules "ask" and "askargs"

## Contributors

Top Contributors


Code Contributors
* Youri van den Bogert
* Yvar Nanlohij

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 4.0.0, ensure the SMW version in `composer.json` is `^4.2.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
