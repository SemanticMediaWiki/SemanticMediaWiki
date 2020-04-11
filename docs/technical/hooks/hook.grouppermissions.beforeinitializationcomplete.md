## SMW::GroupPermissions::BeforeInitializationComplete

* Since: 3.2
* Description: Hook to provide a possibility the modify Semantic MediaWiki's permissions settings before the initialization is completed.
* Reference class: [`GroupPermissions.php`][GroupPermissions.php]

### Signature

```php
use Hooks;

Hooks::register( 'SMW::GroupPermissions::BeforeInitializationComplete', function( &$permissions ) {

	// Assignments have the form of:
	// $permissions['smw_group_x'] = [ 'right_x' => true, 'right_y' => true ];

	// Rights added by Semantic MediaWiki are listed in the
	// `GroupPermissions` class

	return true;
} );
```

[GroupPermissions.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/GroupPermissions.php