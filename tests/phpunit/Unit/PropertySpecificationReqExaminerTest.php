<?php

namespace SMW\Tests;

use SMW\DataItemFactory;
use SMW\PropertySpecificationReqExaminer;
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
	private $protectionValidator;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->protectionValidator = $this->getMockBuilder( '\SMW\Protection\ProtectionValidator' )
			->disableOriginalConstructor()
			->getMock();

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
			new PropertySpecificationReqExaminer( $this->store, $this->protectionValidator )
		);
	}

	/**
	 * @dataProvider propertyProvider
	 */
	public function testCheck( $property, $semanticData, $expected ) {

		$instance = new PropertySpecificationReqExaminer(
			$this->store,
			$this->protectionValidator
		);

		$instance->setSemanticData( $semanticData );

		$this->assertEquals(
			$expected,
			$instance->check( $property )
		);
	}

	public function testCheckDisabledEditProtectionRight() {

		$this->protectionValidator->expects( $this->any() )
			->method( 'getEditProtectionRight' )
			->will( $this->returnValue( false ) );

		$property = $this->dataItemFactory->newDIProperty( '_EDIP' );

		$instance = new PropertySpecificationReqExaminer(
			$this->store,
			$this->protectionValidator
		);

		$this->assertEquals(
			[
				'warning',
				'smw-edit-protection-disabled',
				'Is edit protected'
			],
			$instance->check( $property )
		);
	}

	public function testCheckEnabledCreateProtectionRight() {

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasCreateProtection' )
			->will( $this->returnValue( true ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'getCreateProtectionRight' )
			->will( $this->returnValue( 'foo' ) );

		$property = $this->dataItemFactory->newDIProperty( 'Bar' );

		$instance = new PropertySpecificationReqExaminer(
			$this->store,
			$this->protectionValidator
		);

		$this->assertEquals(
			[
				'warning',
				'smw-create-protection',
				'Bar',
				'foo'
			],
			$instance->check( $property )
		);
	}

	public function testCheckEnabledEditProtectionRight() {

		$this->protectionValidator->expects( $this->any() )
			->method( 'hasEditProtection' )
			->will( $this->returnValue( true ) );

		$this->protectionValidator->expects( $this->any() )
			->method( 'getEditProtectionRight' )
			->will( $this->returnValue( 'foo' ) );

		$property = $this->dataItemFactory->newDIProperty( 'Bar' );

		$instance = new PropertySpecificationReqExaminer(
			$this->store,
			$this->protectionValidator
		);

		$this->assertEquals(
			[
				'error',
				'smw-edit-protection',
				'foo'
			],
			$instance->check( $property )
		);
	}

	public function testCheckImportedVocabTypeMismatch() {

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
			$this->store,
			$this->protectionValidator
		);

		$instance->setSemanticData( $semanticData );

		$this->assertEquals(
			[
				'warning',
				'smw-property-req-violation-import-type',
				'Foo'
			],
			$instance->check( $property )
		);
	}

	public function testCheckChangePropagation() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$semanticData = new SemanticData(
			$property->getDIWikiPage()
		);

		$semanticData->addPropertyObjectValue(
			$this->dataItemFactory->newDIProperty( \SMW\DIProperty::TYPE_CHANGE_PROP ),
			$this->dataItemFactory->newDIBlob( '...' )
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$instance = new PropertySpecificationReqExaminer(
			$store,
			$this->protectionValidator
		);

		$this->assertEquals(
			[
				'error',
				'smw-property-req-violation-change-propagation-locked-error',
				'Foo'
			],
			$instance->check( $property )
		);
	}

	public function testCheckChangePropagationAsWarning() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$semanticData = new SemanticData(
			$property->getDIWikiPage()
		);

		$semanticData->addPropertyObjectValue(
			$this->dataItemFactory->newDIProperty( \SMW\DIProperty::TYPE_CHANGE_PROP ),
			$this->dataItemFactory->newDIBlob( '...' )
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$instance = new PropertySpecificationReqExaminer(
			$store,
			$this->protectionValidator
		);

		$instance->setChangePropagationProtection( false );

		$this->assertEquals(
			[
				'warning',
				'smw-property-req-violation-change-propagation-locked-warning',
				'Foo'
			],
			$instance->check( $property )
		);
	}

	public function propertyProvider() {

		$dataItemFactory = new DataItemFactory();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = [
			$dataItemFactory->newDIProperty( 'Foo' ),
			$semanticData,
			''
		];

		$provider[] = [
			$dataItemFactory->newDIProperty( '_MDAT' ),
			$semanticData,
			''
		];

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'hasProperty' )
			->will( $this->returnValue( false ) );

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_ref_rec' );

		$provider[] = [
			$property,
			$semanticData,
			[
				'error',
				'smw-property-req-violation-missing-fields',
				'Foo',
				'Reference'
			]
		];

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_rec' );

		$provider[] = [
			$property,
			$semanticData,
			[
				'error',
				'smw-property-req-violation-missing-fields',
				'Foo',
				'Record'
			]
		];

		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_eid' );

		$provider[] = [
			$property,
			$semanticData,
			[
				'error',
				'smw-property-req-violation-missing-formatter-uri',
				'Foo'
			]
		];

		return $provider;
	}

}
