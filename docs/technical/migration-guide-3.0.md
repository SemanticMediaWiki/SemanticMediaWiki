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
- Deprecated `PropertyValue::makeUserProperty`, use DataValueFactory::getInstance()->newPropertyValueByLabel;
- Removed `PropertyValue::makeProperty`, use DataValueFactory

## Hooks

- Renamed `smwAddToRDFExport` to `SMW::Exporter::Controller::AddExpData`

## Store

- `Store::getPropertySubjects` is to return an `Iterator` hence an `array`
  type check should be avoided and if necessary use `iterator_to_array` to
  transform a result instance into a standard array

- The fixed property border was moved from 50 to 500

- Table `smw_object_ids`:
  - Field `smw_sortkey` was replaced by `smw_search`, sorting specific representation is now stored in `smw_sort` which can differ from `smw_search` (due to different collation)
  - Field `smw_hash` was added and contains the computed sha1 of `smw_title`, `smw_ns`, `smw_ns`, and `smw_subobject`

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