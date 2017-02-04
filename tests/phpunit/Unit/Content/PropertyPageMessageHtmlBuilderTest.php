<?php

namespace SMW\Tests\Content;

use SMW\Content\PropertyPageMessageHtmlBuilder;
use SMW\DataItemFactory;

/**
 * @covers \SMW\Content\PropertyPageMessageHtmlBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyPageMessageHtmlBuilderTest extends \PHPUnit_Framework_TestCase {

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
			PropertyPageMessageHtmlBuilder::class,
			new PropertyPageMessageHtmlBuilder( $this->store, $this->propertySpecificationReqExaminer )
		);
	}

	/**
	 * @dataProvider propertyProvider
	 */
	public function testCreateMessageBody( $property ) {

		$instance = new PropertyPageMessageHtmlBuilder(
			$this->store,
			$this->propertySpecificationReqExaminer
		);

		$this->assertInternalType(
			'string',
			$instance->createMessageBody( $property )
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
