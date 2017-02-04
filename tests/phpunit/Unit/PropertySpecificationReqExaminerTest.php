<?php

namespace SMW\Tests;

use SMW\PropertySpecificationReqExaminer;
use SMW\DataItemFactory;

/**
 * @covers \SMW\PropertySpecificationReqExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertySpecificationReqExaminerTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PropertySpecificationReqExaminer::class,
			new PropertySpecificationReqExaminer( $this->store )
		);
	}

	/**
	 * @dataProvider propertyProvider
	 */
	public function testCheckOn( $property, $semanticData, $expected ) {

		$instance = new PropertySpecificationReqExaminer(
			$this->store
		);

		$instance->setSemanticData( $semanticData );

		$this->assertEquals(
			$expected,
			$instance->checkOn( $property )
		);
	}

	public function propertyProvider() {

		$dataItemFactory = new DataItemFactory();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = array(
			$dataItemFactory->newDIProperty( 'Foo' ),
			$semanticData,
			''
		);

		$provider[] = array(
			$dataItemFactory->newDIProperty( '_MDAT' ),
			$semanticData,
			''
		);

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'hasProperty' )
			->will( $this->returnValue( false ) );

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_ref_rec' );

		$provider[] = array(
			$property,
			$semanticData,
			array(
				'smw-property-req-violation-missing-fields',
				'Foo',
				'Reference'
			)
		);

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_rec' );

		$provider[] = array(
			$property,
			$semanticData,
			array(
				'smw-property-req-violation-missing-fields',
				'Foo',
				'Record'
			)
		);

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_eid' );

		$provider[] = array(
			$property,
			$semanticData,
			array(
				'smw-property-req-violation-missing-formatter-uri',
				'Foo'
			)
		);

		return $provider;
	}

}
