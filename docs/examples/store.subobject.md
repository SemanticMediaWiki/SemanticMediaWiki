## Using Subobject

```php
$subject = new DIWikiPage( 'Foo', NS_MAIN );

$subobject = new Subobject( $subject->getTitle() );
$subobject->setEmptyContainerForId( 'SomeSubobject' );

$dataValue = DataValueFactory::getInstance()->newDataValueByText(
	'SomeProperty,
	'SomeValue'
);

$subobject->addDataValue(
	$dataValue
);
```

```php
// Create and store SemanticData object and add the subobject
$semanticData = new SemanticData( $subject );

$semanticData->addPropertyObjectValue(
	$subobject->getProperty(),
	$subobject->getContainer()
);

ApplicationFactory::getInstance()->getStore()->updateData(
	$semanticData
);
```

## Using DIContainer

```php
$subject = new DIWikiPage( 'Foo', NS_MAIN );

// Internal subobject reference
$subobjectName = 'SomeSubobject';

$containerSubject = new DIWikiPage(
	$subject->getDBkey(),
	$subject->getNamespace(),
	$subject->getInterwiki(),
	$subobjectName
);

// Create container to host property values assignments
// for the particular subobjectName
$containerSemanticData = new ContainerSemanticData( $containerSubject );

$dataValue = DataValueFactory::getInstance()->newDataValueByText(
	'SomeProperty,
	'SomeValue'
);

$containerSemanticData->addDataValue(
	$dataValue
);
```
```php
// Create and store SemanticData object and add the subobject
$semanticData = new SemanticData( $subject );

$semanticData->addPropertyObjectValue(
	new DIProperty( '_SOBJ' ),
	new DIContainer( $containerSemanticData )
);

ApplicationFactory::getInstance()->getStore()->updateData(
	$semanticData
);
```