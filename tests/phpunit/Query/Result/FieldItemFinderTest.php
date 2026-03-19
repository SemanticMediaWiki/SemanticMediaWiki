<?php

namespace SMW\Tests\Query\Result;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\Query\PrintRequest;
use SMW\Query\Result\FieldItemFinder;
use SMW\Query\Result\ItemFetcher;
use SMW\RequestOptions;
use SMW\Store;

/**
 * @covers \SMW\Query\Result\FieldItemFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FieldItemFinderTest extends TestCase {

	private $dataItemFactory;
	private $dataValueFactory;
	private $store;
	private $itemFetcher;
	private $printRequest;

	protected function setUp(): void {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->itemFetcher = $this->getMockBuilder( ItemFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->printRequest = $this->getMockBuilder( PrintRequest::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FieldItemFinder::class,
			new FieldItemFinder( $this->store, $this->itemFetcher, $this->printRequest )
		);
	}

	public function testGetRequestOptions() {
		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->willReturn( 42 );

		$instance = new FieldItemFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertInstanceOf(
			RequestOptions::class,
			$instance->getRequestOptions()
		);
	}

	public function testFindFor_THIS() {
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo' );

		$this->printRequest->expects( $this->any() )
			->method( 'isMode' )
			->with( PrintRequest::PRINT_THIS )
			->willReturn( true );

		$instance = new FieldItemFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $dataItem ],
			$instance->findFor( $dataItem )
		);
	}

	public function testFindFor_CATS() {
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo' );
		$expected = $this->dataItemFactory->newDIWikiPage( __METHOD__ );

		$this->itemFetcher->expects( $this->once() )
			->method( 'fetch' )
			->with(
				[ $dataItem ],
				$this->dataItemFactory->newDIProperty( '_INST' ) )
			->willReturn( [ $expected ] );

		$this->printRequest->expects( $this->any() )
			->method( 'isMode' )
			->willReturnCallback( static function ( $mode ) {
				return $mode === PrintRequest::PRINT_CATS;
			} );

		$instance = new FieldItemFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findFor( $dataItem )
		);
	}

	public function testFindFor_CCAT() {
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar' );
		$expected = $this->dataItemFactory->newDIWikiPage( __METHOD__ );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$dataItem,
				$this->dataItemFactory->newDIProperty( '_INST' ) )
			->willReturn( [ $expected ] );

		$this->printRequest->expects( $this->any() )
			->method( 'isMode' )
			->willReturnCallback( static function ( $mode ) {
				return $mode === PrintRequest::PRINT_CCAT;
			} );

		$this->printRequest->expects( $this->once() )
			->method( 'getData' )
			->willReturn( $expected );

		$instance = new FieldItemFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $this->dataItemFactory->newDIBoolean( true ) ],
			$instance->findFor( $dataItem )
		);
	}

	public function testFindFor_PROP() {
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar' );
		$expected = $this->dataItemFactory->newDIWikiPage( __METHOD__ );

		$this->itemFetcher->expects( $this->once() )
			->method( 'fetch' )
			->with(
				[ $dataItem ],
				$this->dataItemFactory->newDIProperty( 'Prop' ),
				$this->anything() )
			->willReturn( [ $expected ] );

		$this->printRequest->expects( $this->any() )
			->method( 'isMode' )
			->willReturnCallback( static function ( $mode ) {
				return $mode === PrintRequest::PRINT_PROP;
			} );

		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->willReturn( false );

		$this->printRequest->expects( $this->any() )
			->method( 'getTypeID' )
			->willReturn( '' );

		$this->printRequest->expects( $this->once() )
			->method( 'getData' )
			->willReturn( $this->dataValueFactory->newPropertyValueByLabel( 'Prop' ) );

		$instance = new FieldItemFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findFor( $dataItem )
		);
	}

	public function testFindForWithIteratorAsValueResultOnPRINT_PROP() {
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar' );
		$expected = $this->dataItemFactory->newDIWikiPage( __METHOD__ );

		$this->itemFetcher->expects( $this->once() )
			->method( 'fetch' )
			->with(
				[ $dataItem ],
				$this->dataItemFactory->newDIProperty( 'Prop' ),
				$this->anything() )
			->willReturn( [ $expected ] );

		$this->printRequest->expects( $this->any() )
			->method( 'isMode' )
			->willReturnCallback( static function ( $mode ) {
				return $mode === PrintRequest::PRINT_PROP;
			} );

		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->willReturn( false );

		$this->printRequest->expects( $this->any() )
			->method( 'getTypeID' )
			->willReturn( '' );

		$this->printRequest->expects( $this->once() )
			->method( 'getData' )
			->willReturn(
				$this->dataValueFactory->newPropertyValueByLabel( 'Prop' ) );

		$instance = new FieldItemFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findFor( $dataItem )
		);
	}

	public function testFindForWithBlobValueResultAndRemovedLink() {
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar' );
		$expected = $this->dataItemFactory->newDIBlob( 'bar' );

		$propertyValue = $this->dataValueFactory->newPropertyValueByLabel( 'Prop' );

		$this->itemFetcher->expects( $this->once() )
			->method( 'fetch' )
			->with(
				[ $dataItem ],
				$this->dataItemFactory->newDIProperty( 'Prop' ),
				$this->anything() )
			->willReturn( [ $expected ] );

		$this->printRequest->expects( $this->any() )
			->method( 'isMode' )
			->willReturnCallback( static function ( $mode ) {
				return $mode === PrintRequest::PRINT_PROP;
			} );

		$this->printRequest->expects( $this->any() )
			->method( 'getTypeID' )
			->willReturn( '_txt' );

		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->willReturn( false );

		$this->printRequest->expects( $this->once() )
			->method( 'getData' )
			->willReturn( $propertyValue );

		$instance = new FieldItemFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findFor( $dataItem )
		);
	}

	public function testFindForWithBlobValueResultAndRetainedLink() {
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar' );
		$text = $this->dataItemFactory->newDIBlob( '[[Foo::bar]]' );
		$expected = $this->dataItemFactory->newDIBlob( '[[Foo::bar]]' );

		$propertyValue = $this->dataValueFactory->newPropertyValueByLabel( 'Prop' );

		$this->itemFetcher->expects( $this->once() )
			->method( 'fetch' )
			->with(
				[ $dataItem ],
				$this->dataItemFactory->newDIProperty( 'Prop' ),
				$this->anything() )
			->willReturn( [ $text ] );

		$this->printRequest->expects( $this->any() )
			->method( 'isMode' )
			->willReturnCallback( static function ( $mode ) {
				return $mode === PrintRequest::PRINT_PROP;
			} );

		$this->printRequest->expects( $this->any() )
			->method( 'getOutputFormat' )
			->willReturn( '-raw' );

		$this->printRequest->expects( $this->any() )
			->method( 'getTypeID' )
			->willReturn( '_txt' );

		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->willReturn( false );

		$this->printRequest->expects( $this->once() )
			->method( 'getData' )
			->willReturn( $propertyValue );

		$instance = new FieldItemFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findFor( $dataItem )
		);
	}

}
