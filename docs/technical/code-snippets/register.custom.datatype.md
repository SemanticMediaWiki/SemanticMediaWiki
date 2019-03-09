## Register new datatype

This example shows how to register a new dataType/dataValue in Semantic MediaWiki and the convention for the datatype key is to use `___` as leading identifer to distinguish them from those defined by Semantic MediaWiki itself.

### SMW::DataType::initTypes

```php
use Hooks;
use Foo\DataValues\FooValue;

Hooks::register( 'SMW::DataType::initTypes', function ( $dataTypeRegistry ) {

	$dataTypeRegistry->registerDatatype(
		FooValue::TYPE_ID,
		FooValue::class,
		DataItem::TYPE_BLOB
	);

	$dataTypeRegistry->setOption(
		'foovalue.SomeSetting',
		42
	);

	return true;
};
```

### DataValue representation

```php
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
$fooValue = DataValueFactory::getInstance()->newTypeIdValue(
	'___foo_bar',
	'Bar'
)

$fooValue->getShortWikiText();
```