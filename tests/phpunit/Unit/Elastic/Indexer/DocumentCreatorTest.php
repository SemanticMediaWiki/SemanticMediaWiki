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
class DocumentCreatorTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;

	protected function setUp() : void {

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

		$this->assertInternalType(
			'integer',
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
			->will( $this->onConsecutiveCalls( [ 42, 43 ] ) );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 1001 ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityIdManager ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [ $property ] ) );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $this->equalTo( $property ) )
			->will( $this->returnValue( [] ) );

		$semanticData->expects( $this->any() )
			->method( 'hasProperty' )
			->with( $this->equalTo( new DIProperty( '_REDI' ) ) )
			->will( $this->returnValue( true ) );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->will( $this->returnValue( [] ) );

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
			->will( $this->onConsecutiveCalls( [ 42, 43 ] ) );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 1001 ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityIdManager ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [ $property ] ) );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $this->equalTo( $property ) )
			->will( $this->returnValue( $dataItems ) );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->will( $this->returnValue( [ $semanticData ] ) );

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
			->will( $this->onConsecutiveCalls( [ 42, 43 ] ) );

		$entityIdManager->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 1001 ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityIdManager ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [ $property ] ) );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->with( $this->equalTo( $property ) )
			->will( $this->returnValue( $dataItems ) );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->will( $this->returnValue( [ $semanticData ] ) );

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
