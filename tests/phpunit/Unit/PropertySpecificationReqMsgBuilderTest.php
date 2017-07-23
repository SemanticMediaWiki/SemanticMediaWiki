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
	private $propertySpecificationReqExaminer;

	protected function setUp() {
		parent::setUp();

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getPropertyTableInfoFetcher' ) )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

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
