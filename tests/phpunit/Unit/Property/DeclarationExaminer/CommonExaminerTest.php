<?php

namespace SMW\Tests\Property\DeclarationExaminer;

use SMW\Property\DeclarationExaminer\CommonExaminer;
use SMW\DataItemFactory;
use SMW\SemanticData;
use SMW\ProcessingErrorMsgHandler;

/**
 * @covers \SMW\Property\DeclarationExaminer\CommonExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CommonExaminerTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $entityManager;
	private $semanticData;

	protected function setUp() {
		parent::setUp();

		$this->entityManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $this->entityManager ) );

		$this->semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CommonExaminer::class,
			new CommonExaminer( $this->store, $this->semanticData )
		);
	}

	public function testCheckReservedName() {

		$instance = new CommonExaminer(
			$this->store
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
			'["error","smw-property-name-reserved","Bar"]',
			$instance->getMessagesAsString()
		);
	}

	public function testCheckUniqueness() {

		$this->entityManager->expects( $this->any() )
			->method( 'isUnique' )
			->will( $this->returnValue( false ) );

		$instance = new CommonExaminer(
			$this->store
		);

		$dataItemFactory = new DataItemFactory();

		$instance->check(
			$dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertContains(
			'["error","smw-property-label-uniqueness","Foo"]',
			$instance->getMessagesAsString()
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

		$instance = new CommonExaminer(
			$this->store,
			$semanticData
		);

		$instance->check(
			$dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertContains(
			'smw-property-req-violation-type',
			$instance->getMessagesAsString()
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

		$instance = new CommonExaminer(
			$this->store,
			$semanticData
		);

		$instance->check(
			$dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertContains(
			'{"0":"error","_msgkey":"smw-property-req-error-list","_list":["testFindErrMessages"]}',
			$instance->getMessagesAsString()
		);
	}

}
