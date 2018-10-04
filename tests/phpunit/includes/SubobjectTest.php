<?php

namespace SMW\Tests;

use SMW\DataValueFactory;
use SMW\Subobject;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIBlob;
use Title;

/**
 * @covers \SMW\Subobject
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SubobjectTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Subobject',
			new Subobject( $title )
		);
	}

	public function testSetSemanticWithInvalidIdThrowsException() {

		$instance = new Subobject( Title::newFromText( __METHOD__ ) );

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->setSemanticData( '' );
	}

	public function testSetEmptySemanticData() {

		$instance = new Subobject( Title::newFromText( __METHOD__ ) );
		$instance->setEmptyContainerForId( 'Foo' );

		$this->assertInstanceOf(
			'\Title',
			$instance->getTitle()
		);

		$this->assertInstanceOf(
			'\SMWContainerSemanticData',
			$instance->getSemanticData()
		);

		$this->assertEquals(
			$instance->getSubobjectId(),
			$instance->getSemanticData()->getSubject()->getSubobjectName()
		);
	}

	/**
	 * @dataProvider getDataProvider
	 */
	public function testgetSubobjectId( array $parameters, array $expected ) {

		$instance = $this->acquireInstanceForId(
			Title::newFromText( __METHOD__ ),
			$parameters['identifier']
		);

		if ( $expected['identifier'] !== '_'  ) {
			return $this->assertEquals( $expected['identifier'], $instance->getSubobjectId() );
		}

		$this->assertEquals(
			$expected['identifier'],
			substr( $instance->getSubobjectId(), 0, 1 )
		);
	}

	/**
	 * @dataProvider getDataProvider
	 */
	public function testGetProperty( array $parameters ) {

		$instance = $this->acquireInstanceForId(
			Title::newFromText( __METHOD__ ),
			$parameters['identifier']
		);

		$this->assertInstanceOf(
			'\SMW\DIProperty',
			$instance->getProperty()
		);
	}

	/**
	 * @dataProvider getDataProvider
	 */
	public function testAddDataValue( array $parameters, array $expected ) {

		$instance = $this->acquireInstanceForId(
			Title::newFromText( __METHOD__ ),
			$parameters['identifier']
		);

		foreach ( $parameters['properties'] as $property => $value ){

			$dataValue = DataValueFactory::getInstance()->newDataValueByText(
				$property,
				$value
			);

			$instance->addDataValue( $dataValue );
		}

		$this->assertCount(
			$expected['errors'],
			$instance->getErrors()
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	/**
	 * @dataProvider newDataValueProvider
	 */
	public function testDataValueExaminer( array $parameters, array $expected ) {

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->atLeastOnce() )
			->method( 'findPropertyTypeID' )
			->will( $this->returnValue( $parameters['property']['typeId'] ) );

		$property->expects( $this->atLeastOnce() )
			->method( 'getKey' )
			->will( $this->returnValue( $parameters['property']['key'] ) );

		$property->expects( $this->atLeastOnce() )
			->method( 'getLabel' )
			->will( $this->returnValue( $parameters['property']['label'] ) );

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$parameters['dataItem'],
			$property
		);

		$instance = $this->acquireInstanceForId(
			Title::newFromText( __METHOD__ ),
			'Foo'
		);

		$instance->addDataValue( $dataValue );

		$this->assertCount( $expected['errors'], $instance->getErrors() );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testAddDataValueWithInvalidSemanticDataThrowsException() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new Subobject( Title::newFromText( __METHOD__ ) );

		$this->setExpectedException( '\SMW\Exception\SubSemanticDataException' );
		$instance->addDataValue( $dataValue );
	}

	public function testGetSemanticDataInvalidSemanticDataThrowsException() {

		$instance = new Subobject( Title::newFromText( __METHOD__ ) );

		$this->setExpectedException( '\SMW\Exception\SubSemanticDataException' );
		$instance->getSemanticData();
	}

	/**
	 * @dataProvider errorProvider
	 */
	public function testErrorHandlingOnErrors( $errors, $expected ) {

		$instance = new Subobject( Title::newFromText( __METHOD__ ) );

		foreach ( $errors as $error ) {
			$instance->addError( $error );
		}

		$this->assertCount(
			$expected,
			$instance->getErrors()
		);
	}

	/**
	 * @dataProvider getDataProvider
	 */
	public function testGetContainer( array $parameters ) {
		$instance = $this->acquireInstanceForId(
			Title::newFromText( __METHOD__ ),
			$parameters['identifier']
		);

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$instance->getContainer()
		);
	}

	public function getDataProvider() {

		$provider = [];

		// #0 / asserting conditions for a named identifier
		$provider[] = [
			[
				'identifier' => 'Bar',
				'properties' => [ 'Foo' => 'bar' ]
			],
			[
				'errors' => 0,
				'identifier' => 'Bar',
				'propertyCount'  => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => 'Bar',
			]
		];

		// #1 / asserting conditions for an anon identifier
		$provider[] = [
			[
				'identifier' => '',
				'properties' => [ 'FooBar' => 'bar Foo' ]
			],
			[
				'errors' => 0,
				'identifier' => '_',
				'propertyCount'  => 1,
				'propertyLabels' => 'FooBar',
				'propertyValues' => 'Bar Foo',
			]
		];

		// #2 / asserting conditions
		$provider[] = [
			[
				'identifier' => 'foo',
				'properties' => [ 9001 => 1001 ]
			],
			[
				'errors' => 0,
				'identifier' => 'foo',
				'propertyCount'  => 1,
				'propertyLabels' => [ 9001 ],
				'propertyValues' => [ 1001 ],
			]
		];

		// #3
		$provider[] = [
			[
				'identifier' => 'foo bar',
				'properties' => [ 1001 => 9001, 'Foo' => 'Bar' ]
			],
			[
				'errors' => 0,
				'identifier' => 'foo bar',
				'propertyCount'  => 2,
				'propertyLabels' => [ 1001, 'Foo' ],
				'propertyValues' => [ 9001, 'Bar' ],
			]
		];

		// #4 / asserting that a property with a leading underscore would produce an error
		$provider[] = [
			[
				'identifier' => 'bar',
				'properties' => [ '_FooBar' => 'bar Foo' ]
			],
			[
				'errors' => 1,
				'identifier' => 'bar',
				'propertyCount'  => 1,
				'strictPropertyValueMatch' => false,
				'propertyKeys' => [ '_ERRC' ]
			]
		];

		// #5 / asserting that an inverse property would produce an error
		$provider[] = [
			[
				'identifier' => 'bar',
				'properties' => [ '-FooBar' => 'bar Foo' ]
			],
			[
				'errors' => 1,
				'identifier' => 'bar',
				'propertyCount'  => 1,
				'strictPropertyValueMatch' => false,
				'propertyKeys' => [ '_ERRC' ]
			]
		];

		// #6 / asserting that an improper value for a _wpg property would add "Has improper value for"
		$provider[] = [
			[
				'identifier' => 'bar',
				'properties' => [ 'Foo' => '' ]
			],
			[
				'identifier' => 'bar',
				'errors' => 1,
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 1,
				'propertyKeys' => [ '_ERRC' ]
			]
		];

		return $provider;
	}

	/**
	 * Provides sample data for various dataItem/datValues
	 *
	 * @return array
	 */
	public function newDataValueProvider() {

		$provider = [];

		// #0 Bug 49530
		$provider[] = [
			[
				'property' => [
					'typeId' => '_txt',
					'label'  => 'Blob.example',
					'key'    => 'Blob.example'
				],
				'dataItem' => new SMWDIBlob( '<a href="http://username@example.org/path">Example</a>' )
			],
			[
				'errors' => 0,
				'propertyCount'  => 1,
				'propertyLabels' => 'Blob.example',
				'propertyValues' => '<a href="http://username@example.org/path">Example</a>',
			]
		];

		return $provider;
	}

	/**
	 * @return Subobject
	 */
	private function acquireInstanceForId( Title $title, $id = '' ) {

		$instance = new Subobject( $title );

		if ( $id === '' && $id !== null ) {
			$id = '_abcdef';
		}

		$instance->setEmptyContainerForId( $id );

		return $instance;
	}

	public function errorProvider() {

		$provider = [];

		#0
		$provider[] = [
			[
				'Foo',
				'Foo'
			],
			1
		];

		#1
		$provider[] = [
			[
				'Foo',
				'Bar'
			],
			2
		];

		#2
		$provider[] = [
			[
				[ 'Foo' => 'Bar' ],
				[ 'Foo' => 'Bar' ],
			],
			1
		];

		#3
		$provider[] = [
			[
				[ 'Foo' => 'Bar' ],
				[ 'Bar' => 'Foo' ],
			],
			2
		];

		return $provider;
	}

}
