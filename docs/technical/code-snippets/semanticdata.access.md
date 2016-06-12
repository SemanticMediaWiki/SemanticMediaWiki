## Add semantic data to in-memory (#1202)

To avoid having competing data being stored at a different point in time
during a request aimed for the same subject, property value assignments are collected
and stored in-memory before the finale `Store::updateData` process will
be invoked once.


```php
// Create in-memory ParserOutput transfer object
$parserData = ApplicationFactory::getInstance()->newParserData(
	$parser->getTitle(),
	$parser->getOutput()
);

$subject = $parserData->getSubject();
$property = new DIProperty( 'SomeProperty' );

// Add individual instances of a value for a known property
foreach ( $values as $value ) {
	$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
		$property,
		trim( $value ), // Text value
		false,
		$subject
	);

	$parserData->addDataValue( $dataValue );
}

// Add individual instances of a value for a property only known by
// its textual representation
foreach ( $values as $value ) {
	$dataValue = DataValueFactory::getInstance()->newDataValueByText(
		$property,
		trim( $value ), // Text value
		false,
		$subject
	);

	// Adds the object to the SemanticData container you could also use
	// $parserData->getSemanticData()->addPropertyObjectValue( ...)
	$parserData->addDataValue( $dataValue );
}

// Ensures that objects are pushed to the ParserOutput
$parserData->pushSemanticDataToParserOutput();
```

## Access semantic data currently stored in-memory

```php
// Create in-memory ParserOutput transfer object
$parserData = ApplicationFactory::getInstance()->newParserData(
	$parser->getTitle(),
	$parser->getOutput()
);

// Access to the data store in-memory
$semanticData = $parserData->getSemanticData();
```

## Read semantic data from DB

```php
$subject = new DIWikiPage( 'Foo', NS_MAIN );

$semanticData = ApplicationFactory::getInstance()->getStore->getSemanticData(
	$subject
);
```