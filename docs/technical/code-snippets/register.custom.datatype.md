The following examples demonstrate how to register a new [data type][datatype] using the `SMW::DataType::initTypes` hook in Semantic MediaWiki. The convention is for custom datatypes that the key uses `___` as leading identifier to distinguish them from those defined by Semantic MediaWiki itself.

## Register a new datatype

To register a new data type, two methods are provided:

- Implement the `PluggableDataType` interface or
- Use the provided `DataTypeRegistry::registerDatatype` methods

### Using the `PluggableDataType` interface

The `PluggableDataType` interface is provided with beginning of SMW 3.1 as means to register pluggable data types.

```php
namespace Foo\DataTypes;

use SMW\PluggableDataType;
use Foo\DataValues\FooValue;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class FooPluggableType implements PluggableDataType {

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function getTypeId() {
		return FooValue::TYPE_ID;
	}

	/**
	 * Can return a string or callable (using a factory)
	 *
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function getClass() {
		return [ $this, 'newFooValue' ];
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function getItemType() {
		return DataItem::TYPE_BLOB;
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function getLabel() {
		return false;
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function getAliases() {
		return [];
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function isSubType() {
		return false;
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function isBrowsableType() {
		return false;
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function getCallables() {
		return [];
	}

	/**
	 * @since 0.1
	 *
	 * @return FooValue
	 */
	public function newFooValue( $typeId ) {
		return new FooValue( $typeId );
	}

}
```

```php
use Hooks;
use Foo\DataTypes\FooPluggableType

Hooks::register( 'SMW::DataType::initTypes', function ( $dataTypeRegistry ) {

	$dataTypeRegistry->registerPluggableDataType(
		new FooPluggableType()
	);

	return true;
};
```

### Using `DataTypeRegistry::registerDatatype`

```php
use Hooks;
use Foo\DataValues\FooValue;

Hooks::register( 'SMW::DataType::initTypes', function ( $dataTypeRegistry ) {

	$dataTypeRegistry->registerDatatype(
		FooValue::TYPE_ID,
		FooValue::class,
		DataItem::TYPE_BLOB
	);

	return true;
};
```

### DataValue representation

```php
namespace Foo\DataValues;

class FooValue extends DataValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '___foo_bar';

	/**
	 * @see DataValue::parseUserValue
	 * @note called by DataValue::setUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $userValue ) {
		...
	}
}
```

### Usage

```php
$dataValue = DataValueFactory::getInstance()->newDataValueByType(
	'___foo_bar',
	'Bar'
)

$dataValue->getShortWikiText();
```

[datatype]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datatype.md
