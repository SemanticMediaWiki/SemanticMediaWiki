## Match distinct numeric property value

```php
{{#ask: [[NumericProperty::1111]]
 |?NumericProperty
}}
```

```php
// Create property instance
$property = new DIProperty( 'NumericProperty' );
$property->setPropertyTypeId( '_num' );

$dataItem = new DINumber( 1111 );

$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
	$dataItem,
	$property
);
```

```php
// Create and store the SemanticData object in order to be able to match a value
$subject = new DIWikiPage( 'Foo', NS_MAIN )

$semanticData = new SemanticData( $subject );

$semanticData->addDataValue(
	$dataValue
);

ApplicationFactory::getInstance()->getStore()->updateData(
	$semanticData
);
```

```php
// Create a description that represents the condition
$descriptionFactory = new DescriptionFactory();

$description = $descriptionFactory->newSomeProperty(
	$property,
	$descriptionFactory->newValueDescription( $dataItem )
);

$propertyValue = DataValueFactory::getInstance()->newPropertyValueByLabel(
	'NumericProperty'
);

$description->addPrintRequest(
	new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
);

// Create query object
$query = new Query(
	$description
);

$query->querymode = Query::MODE_INSTANCES;
```

```php
// Try to match condition against the store
$queryResult = ApplicationFactory::getInstance()->getStore()->getQueryResult( $query );

// PHPUnit
$this->assertEquals(
	1,
	$queryResult->getCount()
);
```