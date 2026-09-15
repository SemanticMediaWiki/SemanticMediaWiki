# Semantic MediaWiki 7.3.0

Released on TBD.

This is a [minor release](../RELEASE-POLICY.md). Thus, it contains no breaking changes, only new features and fixes.

Like SMW 7.2.0, this version is compatible with MediaWiki 1.43 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## New features and enhancements

* Added the `$smwgConfigProfiles` setting for applying the configuration profiles in `data/config`, for example `$smwgConfigProfiles = [ 'db-primary-keys' ];`. The `require` in `LocalSettings.php` recommended by the 7.0.0 release notes did not apply these profiles. Replace it with the setting in one edit, since the wiki does not start when a profile is loaded both ways, and then run `update.php` ([#7106](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/7106))

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.3.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
