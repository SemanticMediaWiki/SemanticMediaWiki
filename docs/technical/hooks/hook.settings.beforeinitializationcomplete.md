* Since: 3.1
* Description: Hook to provide a possibility the modify Semantic MediaWiki's encapsulated configuration settings before the initialization is completed.
* Reference class: [`Settings.php`][Settings.php]

### Signature

```php
use Hooks;

Hooks::register( 'SMW::Settings::BeforeInitializationComplete', function( &$configuration ) {

	// Extend the configuration of `smwgNamespacesWithSemanticLinks`
	$configuration['smwgNamespacesWithSemanticLinks'][NS_MAIN] = true

	return true;
} );
```

[Settings.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Settings.php
