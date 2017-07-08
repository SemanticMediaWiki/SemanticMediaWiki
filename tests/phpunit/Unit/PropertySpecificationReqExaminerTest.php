<?php

namespace SMW\Tests;

use SMW\PropertySpecificationReqExaminer;
use SMW\DataItemFactory;
use SMW\SemanticData;

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
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );
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

	public function testCheckOnEditProtectionRight() {

		$property = $this->dataItemFactory->newDIProperty( '_EDIP' );

		$instance = new PropertySpecificationReqExaminer(
			$this->store
		);

		$instance->setEditProtectionRight(
			false
		);

		$this->assertEquals(
			array(
				'warning',
				'smw-edit-protection-disabled',
				'Is edit protected'
			),
			$instance->checkOn( $property )
		);
	}

	public function testCheckOnImportedVocabTypeMismatch() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$semanticData = new SemanticData(
			$property->getDIWikiPage()
		);

		$semanticData->addPropertyObjectValue(
			$this->dataItemFactory->newDIProperty( '_TYPE' ),
			$this->dataItemFactory->newDIProperty( 'Bar' )
		);

		$semanticData->setOption(
			\SMW\PropertyAnnotators\MandatoryTypePropertyAnnotator::IMPO_REMOVED_TYPE,
			$this->dataItemFactory->newDIProperty( '_TYPE' )
		);

		$instance = new PropertySpecificationReqExaminer(
			$this->store
		);

		$instance->setSemanticData( $semanticData );

		$this->assertEquals(
			array(
				'warning',
				'smw-property-req-violation-import-type',
				'Foo'
			),
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
				'error',
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
				'error',
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
				'error',
				'smw-property-req-violation-missing-formatter-uri',
				'Foo'
			)
		);

		return $provider;
	}

}
