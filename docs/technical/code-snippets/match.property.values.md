Examples listed in this document require SMW 2.5.

```php
use SMW\ApplicationFactory;

$applicationFactory = ApplicationFactory::getInstance();
$queryFactory = $applicationFactory->getQueryFactory();

$dataItemFactory = $applicationFactory->getDataItemFactory();

$dataValue = $applicationFactory->getDataValueFactory()->newDataValueByProperty(
	$dataItemFactory->newDIProperty( 'Foo' ),
	'Bar'
);
```
```php
$requestOptions = $queryFactory->newRequestOptions();
$requestOptions->setLimit( 42 );

// Find subjects that match [[Foo::Bar]] and limit the return results to 42
$subjectList = $applicationFactory->getStore()->getPropertySubjects(
	$dataValue->getProperty(),
	$dataValue->getDataItem(),
	$requestOptions
);
```
```php
$requestOptions = $queryFactory->newRequestOptions();
$requestOptions->setLimit( 42 );

// Find all subjects that have a Property:Foo assigned and limit the return results to 42
$subjectList = $applicationFactory->getStore()->getAllPropertySubjects(
	$dataValue->getProperty(),
	$requestOptions
);
```
```php
$descriptionFactory = $queryFactory->newDescriptionFactory();

// Query [[Foo::+]] with a limit of 42 matches
$description = $descriptionFactory->newSomeProperty(
	$dataValue->getProperty(),
	$descriptionFactory->newThingDescription()
);

$query = $queryFactory->newQuery( $description );
$query->setLimit( 42 );

$queryResult = $applicationFactory->getStore()->getQueryResult( $query );
```
```php
$descriptionFactory = $queryFactory->newDescriptionFactory();

// [[Foo::Bar]] with a limit of 42 matches
$description = $descriptionFactory->newFromDataValue(
	$dataValue
);

$query = $queryFactory->newQuery( $description );
$query->setLimit( 42 );

$queryResult = $applicationFactory->getStore()->getQueryResult( $query );
```
