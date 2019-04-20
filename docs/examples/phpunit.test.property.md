## Setup a "Text" type property

```php
$property = new DIProperty( 'Foo' );
$property->setPropertyTypeId( '_txt' );

// Using a dedicated property to created a DV
$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
	$property,
	'Some text'
);

$this->assertInstanceof(
	'\SMWStringValue',
	$dataValue
);

$this->assertEquals(
	'Some text',
	$dataValue->getDataItem()->getString()
);
```

## Using the MockBuilder

```php
$store = $this->getMockBuilder( '\SMW\Store' )
	->disableOriginalConstructor()
	->getMockForAbstractClass();

$store->expects( $this->at( 0 ) )
	->method( 'getPropertyValues' )
	->will( $this->returnValue( array(
		new DIWikiPage( 'SomePropertyOfTypeBlob', SMW_NS_PROPERTY ) ) ) );

$store->expects( $this->at( 1 ) )
	->method( 'getPropertyValues' )
	->with(
		$this->equalTo( new DIWikiPage( 'SomePropertyOfTypeBlob', SMW_NS_PROPERTY ) ),
		$this->anything(),
		$this->anything() )
	->will( $this->returnValue( array(
		\SMWDIUri::doUnserialize( 'http://semantic-mediawiki.org/swivt/1.0#_txt' ) ) ) );

// Inject the store as a mock object due to DIProperty::findPropertyTypeID finding the
// type dynamically when called without explicit declaration
ApplicationFactory::getInstance()->registerObject( 'Store', $store );

// Create a DV from a string value instead of using a dedicated typed
// property (used by #set, #subobject since the user input is a string and not
// a typed object)
$dataValue = DataValueFactory::getInstance()->newDataValueByText(
	'SomePropertyOfTypeBlob',
	'Some text'
);

$this->assertInstanceof(
	'\SMWStringValue',
	$dataValue
);

$this->assertEquals(
	'Some text',
	$dataValue->getDataItem()->getString()
);

// Reset instance to avoid issues with tests that follow hereafter
ApplicationFactory::getInstance()->clear();
```
