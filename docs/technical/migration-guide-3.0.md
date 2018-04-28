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
- Removed `DIProperty::registerPropertyAlias`, deprecated since 2.1, use PropertyRegistry::registerPropertyAlias`
- Deprecated (made private, to idenify users) `PropertyValue::makeUserProperty`, use `DataValueFactory::getInstance()->newPropertyValueByLabel`;
- Removed `PropertyValue::makeProperty`, use DataValueFactory

## Hooks

- Renamed `smwAddToRDFExport` to `SMW::Exporter::Controller::AddExpData`

## Store

- `Store::getPropertySubjects` now returns an `Iterator` and any `array`
  type check should be avoided. If necessary use `iterator_to_array` to
  transform a result instance into a standard array.
- Deprecated `Store::getPropertiesSpecial`, if you used this method to retrieve a list of properties, please consider using the `smwbrowse` API interface to carry out a lookup.
- Deprecated `Store::getUnusedPropertiesSpecial`
- Deprecated `Store::getWantedPropertiesSpecial`
- Deprecated `Store::getStatistics`

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

## Formats

### List formats (incl. list, ol, ul, template)

* Wrapped components of the `list` format in HTML elements
* Added class attributes to HTML elements of `list`, `ol` and `ul` formats to facilitate styling
* Added `plainlist`format
* `template` format becomes alias of the `plainlist` format
* `template` parameter is used when present, even if format is not `template`
* Standardized parameters to templates: All standard parameters start with a `#`
* Dedicated separators for values, properties and result "rows": `sep`, `propsep`, `valuesep`
* Removed final list separator (", and")
* Removed `?` as prefix for template arguments
* Removed `template arguments` parameter
* Removed `columns` parameter

For details see https://gist.github.com/s7eph4n/277e7804fe04954df7d1e15ae874b0d0