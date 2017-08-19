<?php

namespace SMW\Tests;

use SMW\PropertySpecificationReqMsgBuilder;
use SMW\DataItemFactory;

/**
 * @covers \SMW\PropertySpecificationReqMsgBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertySpecificationReqMsgBuilderTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $propertyTableInfoFetcher;
	private $propertySpecificationReqExaminer;

	protected function setUp() {
		parent::setUp();

		$entityManager = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getPropertyTableInfoFetcher', 'getObjectIds' ) )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $this->propertyTableInfoFetcher ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityManager ) );

		$this->propertySpecificationReqExaminer = $this->getMockBuilder( '\SMW\PropertySpecificationReqExaminer' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PropertySpecificationReqMsgBuilder::class,
			new PropertySpecificationReqMsgBuilder( $this->store, $this->propertySpecificationReqExaminer )
		);
	}

	/**
	 * @dataProvider propertyProvider
	 */
	public function testCreateMessage( $property ) {

		$instance = new PropertySpecificationReqMsgBuilder(
			$this->store,
			$this->propertySpecificationReqExaminer
		);

		$instance->checkOn( $property );

		$this->assertInternalType(
			'string',
			$instance->getMessage()
		);
	}

	public function testCheckUniquenesse() {

		$entityManager = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$entityManager->expects( $this->any() )
			->method( 'isUnique' )
			->will( $this->returnValue( false ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getPropertyTableInfoFetcher', 'getObjectIds' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $this->propertyTableInfoFetcher ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityManager ) );

		$instance = new PropertySpecificationReqMsgBuilder(
			$store,
			$this->propertySpecificationReqExaminer
		);

		$dataItemFactory = new DataItemFactory();

		$instance->checkOn(
			$dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertContains(
			'smw-property-uniqueness',
			$instance->getMessage()
		);
	}

	public function propertyProvider() {

		$dataItemFactory = new DataItemFactory();

		$provider[] = array(
			$dataItemFactory->newDIProperty( 'Foo' )
		);

		$provider[] = array(
			$dataItemFactory->newDIProperty( '_MDAT' )
		);

		return $provider;
	}

}
