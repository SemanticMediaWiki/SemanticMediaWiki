# Semantic MediaWiki 4.1.0

Released on January 21st, 2023.

## Summary

This is a [minor release](../RELEASE-POLICY.md). Thus it contains no breaking changes, only bug fixes and new features.

This release improves compatibility with MediaWiki 1.38 and 1.39.

## Changes

* Improved compatibility with MediaWiki 1.38 and 1.39
* Improved compatibility with PHP 8.1 (not complete yet)
* Fixed type error occurring during specific number formatting on PHP 8.0+ (https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5389)
* Fixed bug causing the job queue to be flooded with jobs (https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/4950)
* Fixed issue with the pipe character in the Ask API (https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5348)
* Fixed `rebuildData.php` issue for the `smw_ftp_sesp_usereditcntns` table (https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5313)
* Fixed issue in the category result format (https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5270)
* Fixed upsert warning log spam (https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5307)
* Added user preference that allows enabling or disabling the entity issue panel (https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5345)
* Added support for partial ISO dates (https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5312)
* SMW now ships with updated vocabularies including Schema.org, Dublin Core, FOAF and SKOS
* Various grammar and spelling fixes
* Translation updates

## Contributors

Top contributors

1. Morne Alberts from [Professional Wiki](https://professional.wiki/)
2. Jeroen De Dauw from [Professional Wiki](https://professional.wiki/)
3. Abijeet from [TranslateWiki](https://translatewiki.net)
4. Bernhard Krabina from [KM-A](https://km-a.net/)
5. Karsten Hoffmeyer from [Professional Wiki](https://professional.wiki/)

Code contributions

* translatewiki.net
* Morne Alberts
* Jeroen De Dauw
* Abijeet
* Bernhard Krabina
* Sébastien Beyou
* Hannes
* Hamish Slater
* Karsten Hoffmeyer
* Youri vd Bogert
* Alexander
* Alexander Mashin
* Amir E. Aharoni
* C. Scott Ananian
* D-Groenewegen
* Greg Rundlett
* Mark A. Hershberger
* Markus
* Máté Szabó
* UnknownSkyrimPasserby
* iusgit

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 4.0.0, ensure the SMW version in `composer.json` is `^4.1.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those that have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory

