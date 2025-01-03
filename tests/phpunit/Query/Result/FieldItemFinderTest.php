<?php

namespace SMW\Tests\Query\Result;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\Query\PrintRequest;
use SMW\Query\Result\FieldItemFinder;

/**
 * @covers SMW\Query\Result\FieldItemFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class FieldItemFinderTest extends \PHPUnit\Framework\TestCase {

	private $dataItemFactory;
	private $dataValueFactory;
	private $store;
	private $itemFetcher;
	private $printRequest;

	protected function setUp(): void {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->itemFetcher = $this->getMockBuilder( '\SMW\Query\Result\ItemFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
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
			'SMW\RequestOptions',
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

		$this->printRequest->expects( $this->at( 1 ) )
			->method( 'isMode' )
			->with( PrintRequest::PRINT_CATS )
			->willReturn( true );

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

		$this->printRequest->expects( $this->at( 2 ) )
			->method( 'isMode' )
			->with( PrintRequest::PRINT_CCAT )
			->willReturn( true );

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

		$this->printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with( PrintRequest::PRINT_PROP )
			->willReturn( true );

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

		$this->printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with( PrintRequest::PRINT_PROP )
			->willReturn( true );

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

		$this->printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with( PrintRequest::PRINT_PROP )
			->willReturn( true );

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

		$this->printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with( PrintRequest::PRINT_PROP )
			->willReturn( true );

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
