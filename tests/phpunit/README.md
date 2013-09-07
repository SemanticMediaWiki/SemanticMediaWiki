[PHPUnit][phpunit] provides the necessary environment to execute the in the subsequent directories provided unit tests together with the following base elements. Information about how to work with PHPunit can be found at [smw.org][smw] and [mediawiki.org][mw].

### TestCases
#### SemanticMediaWikiTestCase
SemanticMediaWikiTestCase derives directly from PHPUnit_Framework_TestCase and adds some convenient functions and a assertSemanticData() method.

#### ApiTestCase
ApiTestCase derives from SemanticMediaWikiTestCase and provides a framework for unit tests that directly require access to the Api interface.

#### ParserTestCase
ParserTestCase derives from SemanticMediaWikiTestCase and provides methods normally only needed when the Parser object is the main object of interaction.

#### QueryPrinterTestCase
QueryPrinterTestCase base class for all query printers.

### MockObjectBuilder
Semantic MediaWiki makes it a bit easier to create readable mock objects by using the MockObjectBuilder while object definitions are kept in the MockObjectRepository class.

For example, if a test would need to create a 'Title' mock object it would need to create the following in each test that where rely on a mocked Title.


```php
$mockTitle = $this->getMockBuilder( 'Title' )
	->disableOriginalConstructor()
	->getMock();

$mockTitle->expects( $this->any() )
	->method( 'isSpecialPage' )
	->will( $this->returnValue(  true ) );

...
```

Fortunately, Semantic MediaWiki provides a short cut for most common used mock objects within its test environment.

```php
$this->newMockBuilder()->newObject( 'aConcreteObject', array( ... ) );
```

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
public function testTitleInstanceOnMock( $title, $message ) {
	$this->assertInstanceOf( 'Title', $title, $message );
}

/**
 * @return array
 */
public function titleDataProvider() {

	$provider = array();

	$provider[] = array(
		$this->newMockBuilder()->newObject( 'Title', array( 'isSpecialPage' => false ) ),
		'asserts that Title is a not a special page'
	);

	$provider[] = array(
		$this->newMockBuilder()->newObject( 'Title', array( 'isSpecialPage' => true ) ),
		'asserts that Title is a special page'
	);

	return $provider;
}
```

#### Callbacks
For even greater flexibility, a callback can be invoked to manipulate dependencies where more individual fine tuning is required.

```php
$this->newMockBuilder()->newObject( 'Store', array(
	'getPropertyValues' => array( $this, 'mockStorePropertyValuesCallback' ),
) );
```

### MockUpdateObserver
MockUpdateObserver is used during testing to verify that a correct behaviour between the UpdateObserver and a Observable has been established.

[phpunit]: http://phpunit.de/manual/3.7/en/index.html
[smw]: https://www.semantic-mediawiki.org/wiki/PHPUnit_tests
[mw]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing