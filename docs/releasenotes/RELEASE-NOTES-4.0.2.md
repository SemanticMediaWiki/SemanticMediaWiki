# Semantic MediaWiki 4.0.2

Released on TBD, 2022.

## Summary

This is a [patch release](../RELEASE-POLICY.md), meaning that it contains only fixes and no breaking changes.

TBD

## Upgrading

Get the new version via Composer:

* Step 1: if you are upgrading from SMW older than 4.0.0, ensure the SMW version in `composer.json` is `^4.0.2`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

No need to run update.php or any other migration scripts.

## Changes

* Fixed Resource Loader warning when loading the Factbox module. Thanks [Jeroen De Dauw](https://entropywins.wtf/) & [Professional.Wiki](https://professional.wiki/).


