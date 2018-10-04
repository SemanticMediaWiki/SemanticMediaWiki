<?php

namespace SMW\Tests;

use SMW\DataItemFactory;
use SMW\ProcessingErrorMsgHandler;
use SMW\PropertySpecificationReqMsgBuilder;
use SMW\SemanticData;

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
			->setMethods( [ 'getPropertyTableInfoFetcher', 'getObjectIds' ] )
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

		$instance->check( $property );

		$this->assertInternalType(
			'string',
			$instance->getMessage()
		);
	}

	public function testFindErrMessages() {

		$dataItemFactory = new DataItemFactory();
		$subject = $dataItemFactory->newDIWikiPage( 'Test', NS_MAIN );

		$semanticData = new SemanticData(
			$subject
		);

		$processingErrorMsgHandler = new ProcessingErrorMsgHandler(
			$subject
		);

		$processingErrorMsgHandler->addToSemanticData(
			$semanticData,
			$processingErrorMsgHandler->newErrorContainerFromMsg( [ 'testFindErrMessages' ] )
		);

		$instance = new PropertySpecificationReqMsgBuilder(
			$this->store,
			$this->propertySpecificationReqExaminer
		);

		$instance->setSemanticData( $semanticData );

		$instance->check(
			$dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertContains(
			'smw-property-error-list',
			$instance->getMessage()
		);

		$this->assertContains(
			'testFindErrMessages',
			$instance->getMessage()
		);
	}

	public function testErrorOnCompetingTypes() {

		$dataItemFactory = new DataItemFactory();
		$subject = $dataItemFactory->newDIWikiPage( 'Test', NS_MAIN );

		$semanticData = new SemanticData(
			$subject
		);

		$semanticData->addPropertyObjectValue(
			$dataItemFactory->newDIProperty( '_TYPE' ),
			$dataItemFactory->newDIBlob( '_num' )
		);

		$semanticData->addPropertyObjectValue(
			$dataItemFactory->newDIProperty( '_TYPE' ),
			$dataItemFactory->newDIBlob( '_dat' )
		);

		$instance = new PropertySpecificationReqMsgBuilder(
			$this->store,
			$this->propertySpecificationReqExaminer
		);

		$instance->setSemanticData( $semanticData );

		$instance->check(
			$dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertContains(
			'smw-property-req-violation-type',
			$instance->getMessage()
		);
	}

	public function testCheckUniqueness() {

		$entityManager = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$entityManager->expects( $this->any() )
			->method( 'isUnique' )
			->will( $this->returnValue( false ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyTableInfoFetcher', 'getObjectIds' ] )
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

		$instance->check(
			$dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertContains(
			'smw-property-uniqueness',
			$instance->getMessage()
		);
	}

	public function testCheckReservedName() {

		$instance = new PropertySpecificationReqMsgBuilder(
			$this->store,
			$this->propertySpecificationReqExaminer
		);

		$instance->setPropertyReservedNameList(
			[
				'Bar'
			]
		);

		$dataItemFactory = new DataItemFactory();

		$instance->check(
			$dataItemFactory->newDIProperty( 'Bar' )
		);

		$this->assertContains(
			'smw-property-name-reserved',
			$instance->getMessage()
		);
	}

	public function propertyProvider() {

		$dataItemFactory = new DataItemFactory();

		$provider[] = [
			$dataItemFactory->newDIProperty( 'Foo' )
		];

		$provider[] = [
			$dataItemFactory->newDIProperty( '_MDAT' )
		];

		return $provider;
	}

}
