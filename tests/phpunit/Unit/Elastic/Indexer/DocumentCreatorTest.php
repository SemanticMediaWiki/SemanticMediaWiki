<?php

namespace SMW\Tests\Unit\Elastic\Indexer;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\Boolean;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\Uri;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Elastic\Indexer\Document;
use SMW\Elastic\Indexer\DocumentCreator;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\Elastic\Indexer\DocumentCreator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class DocumentCreatorTest extends TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
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
		$subject = WikiPage::newFromText( 'Foo' );
		$subject->setOption( 'sort', 'abc' );

		$property = new Property( 'FooProp' );

		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturn( 42 );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 1001 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
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
			->with( new Property( '_REDI' ) )
			->willReturn( true );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [] );

		$instance = new DocumentCreator( $this->store );
		$document = $instance->newFromSemanticData( $semanticData );

		$this->assertInstanceOf(
			Document::class,
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
		$subject = WikiPage::newFromText( 'Foo' );
		$subject->setOption( 'sort', 'abc' );

		$property = new Property( 'FooProp' );

		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturn( 42 );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 1001 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
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
			Document::class,
			$instance->newFromSemanticData( $semanticData )
		);
	}

	/**
	 * @dataProvider dataItemsProvider
	 */
	public function testNewFromSemanticData_SubDataType( $dataItems ) {
		$subject = WikiPage::newFromText( 'Foo' );
		$subject->setOption( 'sort', 'abc' );

		$property = new Property( '_SOBJ' );

		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPageID' )
			->willReturn( 42 );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 1001 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
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
			Document::class,
			$instance->newFromSemanticData( $semanticData )
		);
	}

	public function dataItemsProvider() {
		yield 'page_type' => [
			[ WikiPage::newFromText( 'Bar' ) ]
		];

		yield 'text_type' => [
			[ new Blob( 'test' ) ]
		];

		yield 'num_type' => [
			[ new Number( 9999 ) ]
		];

		yield 'bool_type' => [
			[ new Boolean( true ) ]
		];

		yield 'uri_type' => [
			[ Uri::doUnserialize( 'http://example.org' ) ]
		];

		yield 'dat_type' => [
			[ Time::newFromTimestamp( '1362200400' ) ]
		];
	}

}
