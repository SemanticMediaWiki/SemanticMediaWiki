## SMW::GroupPermissions::BeforeInitializationComplete

* Since: 3.2
* Description: Hook to provide a possibility the modify Semantic MediaWiki's permissions settings before the initialization is completed.
* Reference class: [`GroupPermissions.php`][GroupPermissions.php]

### Signature

```php
use Hooks;

Hooks::register( 'SMW::GroupPermissions::BeforeInitializationComplete', function( &$permissions ) {

	return true;
} );
```

[GroupPermissions.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Permission/GroupPermissions.php