## Register custom predefined property

This example shows how to register a custom predefined property in Semantic MediaWiki with the convention that for the property key `__` as leading identifer should be used to distinguish them from those defined by Semantic MediaWiki itself.

### SMW::Property::initProperties

```php
use Hooks;
use SMW\PropertyRegistry;

Hooks::register( 'SMW::Property::initProperties', function ( PropertyRegistry $propertyRegistry ) {

	// Canonical label
	define( 'PROP_LABEL_FOOBAR_KEY', 'Foobar key' );

	$definitions = [
		'__foobar_key' => [
			'label' => PROP_LABEL_FOOBAR_KEY,

			// Can contain default or custom datatypes (see
			// how to register custom datatypes)
			'type'  => '_txt',

			// MW message key
			'alias' => 'foobar-property-alias-key',

			// Is viewable on the facbox
			'viewable' => true,

			// Can be used by a user to create an annotation
			'annotable' => true
		]
	];

	foreach ( $definitions as $definition ) {

		$propertyRegistry->registerProperty(
			$propertyId,
			$definition['type'],
			$definition['label'],
			$definition['viewable'],
			$definition['annotable']
		);

		$propertyRegistry->registerPropertyAlias(
			$propertyId,
			wfMessage( $definition['alias'] )->text()
		);

		$propertyRegistry->registerPropertyAliasByMsgKey(
			$propertyId,
			$definition['alias']
		);
	}

	return true;
};
```