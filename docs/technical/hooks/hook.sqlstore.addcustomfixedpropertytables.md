* Since: 2.3
* Description: Hook to add fixed property table definitions
* Reference class: [`PropertyTableDefinitionBuilder.php`][PropertyTableDefinitionBuilder.php]

### Signature

```php
use Hooks;

Hooks::register( 'SMW::SQLStore::AddCustomFixedPropertyTables', function( array &$customFixedProperties, &$propertyTablePrefix ) {

	return true;
} );
```

## See also

- For a usage example, see the [`SemanticCite`](https://github.com/SemanticMediaWiki/SemanticCite) or [`SemanticExtraSpecialProperties`](https://github.com/SemanticMediaWiki/SemanticExtraSpecialProperties) extension

[PropertyTableDefinitionBuilder.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/PropertyTableDefinitionBuilder.php