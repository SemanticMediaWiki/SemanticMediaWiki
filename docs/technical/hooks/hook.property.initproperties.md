* Since: 2.1
* Description: Hook to add additional predefined properties (`smwInitProperties` was deprecated with 2.1)
* Reference class: [`PropertyRegistry.php`][PropertyRegistry.php]

### Signature

```php
use Hooks;
use SMW\PropertyRegistry;

Hooks::register( 'SMW::Property::initProperties', function( PropertyRegistry $propertyRegistry ) {

	return true;
} );
```

## See also

- [`hook.property.initproperties.md`][hook.property.initproperties.md]

[PropertyRegistry.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/PropertyRegistry.php
[hook.property.initproperties.md]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/hook.property.initproperties.md