### MockObjectBuilder
Semantic MediaWiki makes it a bit easier to create readable mock objects by using its own MockObjectBuilder with object definitions specified in the MockObjectRepository class.

```php
public function newMockBuilder() {

	$builder = new MockObjectBuilder();
	$builder->registerRepository( new CoreMockObjectRepository() );
	$builder->registerRepository( new MediaWikiMockObjectRepository() );

	return $builder;
}
```

Semantic MediaWiki provides a short cut for most common mock objects used within its test environment.
```php
$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
	'isSpecialPage' => true
) );
```

#### Example
```php
/**
 * @dataProvider titleDataProvider
 * @since 1.9
 */
public function testTitleInstanceOnMock( $title ) {
	$this->assertInstanceOf( 'Title', $title );
}
```

For example, if a test would need to create a 'Title' mock object it would need to add the follwing to each test that where rely on a mocked Title.

```php
$mockTitle = $this->getMockBuilder( 'Title' )
	->disableOriginalConstructor()
	->getMock();

$mockTitle->expects( $this->any() )
	->method( 'isSpecialPage' )
	->will( $this->returnValue(  true ) );

...
```

```php
/**
 * @return array
 */
public function titleDataProvider() {

	$provider = array();

	$provider[] = array(
		$this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage' => false
		) )
	);

	$provider[] = array(
		$this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage' => true
		) )
	);

	return $provider;
}
```

```php
/**
 * Demonstrates how to use the MockObjectBuilder for a more complex
 * object composition
 */
public function mockSkinComposition() {

	$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
		'isSpecialPage' => true
	) );

	$mockOutputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
		'getTitle'  => $mockTitle
		...
	) );

	$mockSkin = $this->newMockBuilder()->newObject( 'Skin', array(
		'getTitle'  => $mockOutputPage->getTitle(),
		'getOutput' => $mockOutputPage
		...
	) );

	return $mockSkin;
}
```
#### Callbacks
For even greater flexibility, a callback can be invoked to manipulate dependencies where more individual fine tuning is required.

```php
$this->newMockBuilder()->newObject( 'Store', array(
	'getPropertyValues' => array( $this, 'mockStorePropertyValuesCallback' ),
) );
```

### Other mock objects
#### MockUpdateObserver
MockUpdateObserver is used during testing to verify that a correct behaviour between the UpdateObserver and a Observable has been established.

#### MockSuperUser
Object to interact with MW's User class.

[phpunit]: http://phpunit.de/manual/3.7/en/index.html
[smw]: https://www.semantic-mediawiki.org/wiki/PHPUnit_tests
[mw]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing