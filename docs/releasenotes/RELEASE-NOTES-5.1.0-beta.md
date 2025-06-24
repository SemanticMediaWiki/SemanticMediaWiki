# Semantic MediaWiki 5.1.0-beta

Not a release yet.

This is a minor feature release. It introduces backward-compatible enhancements to the API serialization logic and improves the developer experience for JSON-based data consumers.

Like SMW 5.0.0, this version is compatible with MediaWiki 1.39 up to 1.43 and PHP 8.1 up to 8.4.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 5.0.0, ensure the SMW version in `composer.local.json` is `^5.1.0-beta`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory

## Changes

* Added support for serializing inverse (incoming) properties in API output
* Updated `SemanticDataSerializer.php` to include `direction` key (`direct` or `inverse`) for each property
* Maintained backward compatibility with existing serialization format
* Added new JSONScript test cases for inverse property serialization
* Internal refactoring for cleaner separation of direct and inverse property logic
