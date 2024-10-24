# Semantic MediaWiki 5.0.0

Released on Octobar 24th, 2024.

## Summary

This is a [minor release](../RELEASE-POLICY.md). Thus, it contains no breaking changes, only bug fixes and new features.
This release introduces the several modification across multiple classes and test files. 


## Changes

* [#4348](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4348#issuecomment-552868424) - according to the mwjames suggestion new interface has been introduced to cover new output marker and change the syntax 
in 'ask' query
* new custom formatters can be created if needed which will use the interface methods
* [#5739](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5739) - instead of `nolink` output marker `link=` introduced as well as `thclass=unsortable` which is used to set table headers to unsortable


## Contributors

Top Contributors

* Bertrand Gorge
* Niklas Laxström
* Mark A. Hershberger
* Jaider Andrade Ferreira
* Youri van den Bogert
* alistair3149

Code Contributors

* James Hong Kong
* Bertrand Gorge
* Niklas Laxström
* Mark A. Hershberger
* Jaider Andrade Ferreira
* alistair3149
* Yvar Nanlohij
* thomas-topway-it
* Robert Vogel
* Jeroen De Dauw
* Karsten Hoffmeyer
* Translatewiki.net
* Marko Ilic

## Upgrading

**Note:** You need to run either "update.php" or "setupStore.php". Apart from that, no other script needs to be run.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 4.0.0, ensure the SMW version in `composer.json` is `^4.2.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`
* Step 3: run either MediaWiki's update.php or SemanticMediaWiki's
  [setupStore.php maintenance script](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_setupStore.php)

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
* Step 3: run either MediaWiki's update.php or SemanticMediaWiki's
  [setupStore.php maintenance script](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_setupStore.php)

