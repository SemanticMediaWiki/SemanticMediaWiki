# Semantic MediaWiki 6.1.0

Released on TBD.

This is a [feature release](../RELEASE-POLICY.md). Thus, it contains new features and bug fixes, but no breaking changes.

Like SMW 6.0.x, this version is compatible with MediaWiki 1.43 up to 1.44 and PHP 8.1 up to 8.4.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Changes

* 

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 6.0.0, ensure the SMW version in `composer.local.json` is `^6.1.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
