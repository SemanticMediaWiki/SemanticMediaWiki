# Semantic MediaWiki 4.1.2

Released on July 29th, 2023.

## Summary

This is a [patch release](../RELEASE-POLICY.md). Thus it contains only bug fixes. No new features or breaking changes.

This release improves compatibility with MediaWiki 1.39 and PHP 8.1.

## Changes

* Added compatibility with wikimedia/cdb 3.x, needed for MediaWiki 1.41
* Fixed PHP 8.1 compatibility issue (thanks HamishSlater)
* Fixed change propagation issue (thanks Niklas Laxström)
* Fixed warning being spammed in the logs (thanks octfx)
* Fixed logging flag issue (thanks cicalese)
* Avoid unnecessary primary DB query (thanks Máté Szabó)
* Stop using PrevNextNavigationRenderer as it is deprecated in MediaWiki 1.39 (thanks Abijeet)
* Translation updates

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 4.0.0, ensure the SMW version in `composer.json` is `^4.1.2`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those that have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
