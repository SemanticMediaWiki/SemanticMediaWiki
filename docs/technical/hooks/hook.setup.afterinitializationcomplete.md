## SMW::Setup::AfterInitializationComplete

* Since: 3.0
* Description: Hook allows to modify global configuration after initialization of Semantic MediaWiki is completed.
* Reference class: [`Setup.php`][Setup.php]

### Signature

```php
use Hooks;

Hooks::register( 'SMW::Setup::AfterInitializationComplete', function( &$vars ) {

	// #2565
	// It is suggested to use `SMW::GroupPermissions::BeforeInitializationComplete` for
	// the following case:
	unset( $vars['wgGroupPermissions']['smwcurator'] );

	return true;
} );
```

[Setup.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/includes/Setup.php
