* Since: 3.2
* Description: Hook allows to register schema types
* Reference class: [`SchemaTypes.php`][SchemaTypes.php]

### Signature

```php
use Hooks;
use SMW\Schema\SchemaTypes;

Hooks::register( 'SMW::Schema::RegisterSchemaTypes', function( SchemaTypes $schemaTypes ) {

	$params = [
		'group' => FOO_GROUP,
		'validation_schema' => $schemaTypes->withDir( 'foo-schema.v1.json' ),
		'type_description' => 'smw-schema-description-foo-schema',
		'change_propagation' => [ '_FOO_SCHEMA' ],
		'usage_lookup' => '_FOO_SCHEMA'
	];

	$schemaTypes->registerSchemaType( 'FOO_SCHEMA', $params );

	return true;
} );
```

[RevisionGuard.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/SchemaTypes.php