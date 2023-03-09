# Semantic MediaWiki 4.1.1

Released on March 9, 2023.

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

Top contributors

1. Amir E. Aharoni from [TranslateWiki](https://translatewiki.net)
2. Morne Alberts from [Professional Wiki](https://professional.wiki/)
3. CosmicAlpha from [Professional Wiki](https://professional.wiki/)
4. Jeroen De Dauw from [Professional Wiki](https://professional.wiki/)
5. MPThLee

Code contributions

* Amir E. Aharoni
* translatewiki.net
* CosmicAlpha
* Morne Alberts
* MPThLee
* Jeroen De Dauw
* Sébastien Beyou
* Greg Rundlett
* Marijn van Wezel
* Meno25
* Sophivorus
* Vedmaka
* Will Cohen

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 4.0.0, ensure the SMW version in `composer.json` is `^4.1.1`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those that have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
