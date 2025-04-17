# Semantic MediaWiki 5.0.1

Released on April 14th, 2025.

This is a [patch release](../RELEASE-POLICY.md). Thus, it contains only bug fixes, no new features, and no breaking changes.

Like SMW 5.0.0, this version is compatible with MediaWiki 1.39 up to 1.43 and PHP 8.1 up to 8.4.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 5.0.0, ensure the SMW version in `composer.local.json` is `^5.0.1`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory

## Changes

* Fixed title handling for pages with namespace-like prefixes
* Fixed broken code references to `SMW\Message`
* Fixed issue with original parameter in change propagation jobs
* Fixed previewing edits with a factbox
