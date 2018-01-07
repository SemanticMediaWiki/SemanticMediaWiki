# Migration guide

## Maintenance scripts

- If you are still using maintenance scripts starting with the `SMW_` prefix
  you must now migrate to the new maintenance spript names. See the help pages
  on [maintenance scrips](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_scripts) for further information.

## Resources export

- Resources are now being exported as Internationalized Resource Identifiers (IRI).
  This means that the URIs are now being exported using Universal Coded Character
  Set (UCS) instead of American Standard Code for Information Interchange (ASCII).
  See the help page on configuration parameter [`$smwgExportResourcesAsIri`](https://www.semantic-mediawiki.org/wiki/Help:$smwgExportResourcesAsIri)
  for furhter information.

## Removed classes and methods

- Removed `DIProperty::findPropertyID`, deprecated since 2.1, use PropertyRegistry::findPropertyIdByLabel
- Removed `DIProperty::getPredefinedPropertyTypeId`, deprecated since 2.1, use PropertyRegistry::getPropertyValueTypeById
- Removed `DIProperty::findPropertyLabel`, deprecated since 2.1, use PropertyRegistry::findPropertyLabelById
- Removed `DIProperty::registerProperty`, deprecated since 2.1, use PropertyRegistry::registerProperty
- Removed `DIProperty::registerPropertyAlias`, deprecated since 2.1, use PropertyRegistry::registerPropertyAlias

## Store

- `Store::getPropertySubjects` is to return an `Iterator` hence an `array`
  type check should be avoided and if necessary use `iterator_to_array` to
  transform a result instance into a standard array

### Register predefined property

```
\Hooks::register( 'SMW::Property::initProperties', function( $propertyRegistry ) {

	$propertyRegistry->registerProperty( '__FOO', '_txt', 'Foo' );

	$propertyRegistry->registerPropertyDescriptionByMsgKey(
		'__FOO',
		'a-mediawiki-msg-key-with-a-description'
	);

	return true;
} );
```
