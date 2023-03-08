# Semantic MediaWiki 4.1.1

Released on March TODO, 2023.

## Summary

This is a [patch release](../RELEASE-POLICY.md). Thus it contains only bug fixes. No new features or breaking changes.

This release improves compatibility with MediaWiki 1.39 and PHP 8.1.

## Changes

* Improved MediaWiki 1.39 compatibility (thanks Jeroen De Dauw)
* Improved PHP 8.1 compatibility (thanks Morne Alberts)
* Fixed SQLite compatibility issue (thanks Marijn van Wezel)
* Fixed Maps compatibility issue (thanks Universal Omega)
* Various grammar and spelling fixes (thanks Amir E. Aharoni)
* Translation updates

## Technical notes

* Dropped dependence on `onoi/shared-resources` and added copies of these resource loader modules to SMW: `onoi.qtip`, `onoi.rangeslider`, `onoi.blobstore`, `onoi.clipboard`, `noi.dataTables`. This fixes https://github.com/SemanticMediaWiki/SemanticResultFormats/issues/766

## Contributors

Top contributors TODO

1. Morne Alberts from [Professional Wiki](https://professional.wiki/)
2. Jeroen De Dauw from [Professional Wiki](https://professional.wiki/)
3. Abijeet from [TranslateWiki](https://translatewiki.net)
4. Bernhard Krabina from [KM-A](https://km-a.net/)
5. Karsten Hoffmeyer from [Professional Wiki](https://professional.wiki/)

Code contributions TODO

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

* Step 1: if you are upgrading from SMW older than 4.0.0, ensure the SMW version in `composer.json` is `^4.1.1`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those that have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory

