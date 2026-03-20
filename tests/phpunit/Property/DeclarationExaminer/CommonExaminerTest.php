<?php

namespace SMW\Tests\Property\DeclarationExaminer;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\ProcessingErrorMsgHandler;
use SMW\Property\DeclarationExaminer\CommonExaminer;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\Property\DeclarationExaminer\CommonExaminer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class CommonExaminerTest extends TestCase {

	private $store;
	private $entityManager;
	private $semanticData;

	protected function setUp(): void {
		parent::setUp();

		$this->entityManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->entityManager );

		$this->semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CommonExaminer::class,
			new CommonExaminer( $this->store, $this->semanticData )
		);
	}

	public function testCheckNamespace() {
		$instance = new CommonExaminer(
			$this->store
		);

		$instance->setNamespacesWithSemanticLinks( [] );

		$dataItemFactory = new DataItemFactory();

		$instance->check(
			$dataItemFactory->newDIProperty( 'Bar' )
		);

		$this->assertStringContainsString(
			'["error","smw-property-namespace-disabled"]',
			$instance->getMessagesAsString()
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

		$this->assertStringContainsString(
			'["error","smw-property-name-reserved","Bar"]',
			$instance->getMessagesAsString()
		);
	}

	public function testCheckUniqueness() {
		$this->entityManager->expects( $this->any() )
			->method( 'isUnique' )
			->willReturn( false );

		$instance = new CommonExaminer(
			$this->store
		);

		$dataItemFactory = new DataItemFactory();

		$instance->check(
			$dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertStringContainsString(
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

		$this->assertStringContainsString(
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

		$this->assertStringContainsString(
			'{"0":"error","_msgkey":"smw-property-req-error-list","_list":["testFindErrMessages"]}',
			$instance->getMessagesAsString()
		);
	}

}
