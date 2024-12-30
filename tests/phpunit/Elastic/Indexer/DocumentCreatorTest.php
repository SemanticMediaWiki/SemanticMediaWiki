<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\DocumentCreator;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Elastic\Indexer\DocumentCreator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DocumentCreatorTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DocumentCreator::class,
			new DocumentCreator( $this->store )
		);
	}

	public function testGetDocumentCreationDuration() {
		$instance = new DocumentCreator( $this->store );

		$this->assertIsInt(

			$instance->getDocumentCreationDuration()
		);
	}

	public function testNewFromSemanticData_RedirectDelete() {
		$subject = DIWikiPage::newFromText( 'Foo' );
		$subject->setOption( 'sort', 'abc' );

		$property = new DIProperty( 'FooProp' );

		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturnOnConsecutiveCalls( [ 42, 43 ] );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 1001 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $property )
			->willReturn( [] );

		$semanticData->expects( $this->any() )
			->method( 'hasProperty' )
			->with( new DIProperty( '_REDI' ) )
			->willReturn( true );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [] );

		$instance = new DocumentCreator( $this->store );
		$document = $instance->newFromSemanticData( $semanticData );

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Document',
			$document
		);

		$this->assertTrue(
			$document->isType( 'type/delete' )
		);
	}

	/**
	 * @dataProvider dataItemsProvider
	 */
	public function testNewFromSemanticData( $dataItems ) {
		$subject = DIWikiPage::newFromText( 'Foo' );
		$subject->setOption( 'sort', 'abc' );

		$property = new DIProperty( 'FooProp' );

		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturnOnConsecutiveCalls( [ 42, 43 ] );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 1001 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $property )
			->willReturn( $dataItems );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [ $semanticData ] );

		$instance = new DocumentCreator( $this->store );

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Document',
			$instance->newFromSemanticData( $semanticData )
		);
	}

	/**
	 * @dataProvider dataItemsProvider
	 */
	public function testNewFromSemanticData_SubDataType( $dataItems ) {
		$subject = DIWikiPage::newFromText( 'Foo' );
		$subject->setOption( 'sort', 'abc' );

		$property = new DIProperty( '_SOBJ' );

		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturnOnConsecutiveCalls( [ 42, 43 ] );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 1001 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $property )
			->willReturn( $dataItems );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [ $semanticData ] );

		$instance = new DocumentCreator( $this->store );

		$this->assertInstanceOf(
			'\SMW\Elastic\Indexer\Document',
			$instance->newFromSemanticData( $semanticData )
		);
	}

	public function dataItemsProvider() {
		yield 'page_type' => [
			[ DIWikiPage::newFromText( 'Bar' ) ]
		];

		yield 'text_type' => [
			[ new \SMWDIBlob( 'test' ) ]
		];

		yield 'num_type' => [
			[ new \SMWDINumber( 9999 ) ]
		];

		yield 'bool_type' => [
			[ new \SMWDIBoolean( true ) ]
		];

		yield 'uri_type' => [
			[ \SMWDIUri::doUnserialize( 'http://example.org' ) ]
		];

		yield 'dat_type' => [
			[ \SMWDITime::newFromTimestamp( '1362200400' ) ]
		];
	}

}
