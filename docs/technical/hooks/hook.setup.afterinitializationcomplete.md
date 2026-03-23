## SMW::Setup::AfterInitializationComplete

* Since: 3.0
* Description: Hook allows to modify global configuration after initialization of Semantic MediaWiki is completed.
* Reference class: [`Setup.php`][Setup.php]

### Signature

```php
use MediaWiki\MediaWikiServices;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::Setup::AfterInitializationComplete', function( &$vars ) {

	return true;
} );
```

To modify SMW's permissions, use standard `$wgGroupPermissions` overrides
in `LocalSettings.php` (permissions are declared in `extension.json` since SMW 7.0):

```php
$wgGroupPermissions['smwcurator']['smw-patternedit'] = false;
```

[Setup.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/includes/Setup.php
