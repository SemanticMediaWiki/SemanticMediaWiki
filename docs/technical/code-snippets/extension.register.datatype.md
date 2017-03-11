## Register new datatype

This example shows how to register a new dataType/dataValue in Semantic MediaWiki.

```php
\Hooks::register( 'SMW::DataType::initTypes', function ( $dataTypeRegistry ) {

	$dataTypeRegistry->registerDatatype(
		'___foo_bar',
		'\Foo\DataValues\FooValue',
		DataItem::TYPE_BLOB
	);

	// Since 2.4
	$dataTypeRegistry->setOption(
		'settingRelevantForTheFactoryProcess',
		42
	);

	return true;
};
```

```php
class FooValue extends DataValue {

	/**
	 * @see DataValue::parseUserValue
	 * @note called by DataValue::setUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $userValue ) {
		if ( $this->getOption( 'settingRelevantForTheFactoryProcess' ) === 42 ) {

		}
	}
}
```

```php
$fooValue = DataValueFactory::getInstance()->newTypeIdValue(
	'___foo_bar',
	'Bar'
)

$fooValue->getShortWikiText();
```