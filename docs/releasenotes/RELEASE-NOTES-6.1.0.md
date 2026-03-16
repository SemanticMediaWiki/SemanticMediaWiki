# Semantic MediaWiki 6.1.0

Released on TBD.

This is a [feature release](../RELEASE-POLICY.md). Thus, it contains new features and bug fixes, but no breaking changes.

Like SMW 6.0.x, this version is compatible with MediaWiki 1.43 up to 1.44 and PHP 8.1 up to 8.4.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Changes

### Deprecations

* `enableSemantics()` is deprecated and now a no-op. `wfLoadExtension( 'SemanticMediaWiki' )` alone is sufficient to install SMW, aligning with standard MediaWiki extension conventions. The RDF namespace URI is now auto-derived from `Special:URIResolver` when not explicitly set. Users who set a custom `$smwgNamespace` in `LocalSettings.php` are unaffected.

  If you used configuration preloading via `enableSemantics`:

  ```php
  // Before (deprecated)
  enableSemantics( 'example.org' )->loadDefaultConfigFrom( 'media.php' );
  ```

  Replace with a direct `require`:

  ```php
  // After
  wfLoadExtension( 'SemanticMediaWiki' );
  require "$IP/extensions/SemanticMediaWiki/data/config/media.php";
  ```

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 6.0.0, ensure the SMW version in `composer.local.json` is `^6.1.0`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
