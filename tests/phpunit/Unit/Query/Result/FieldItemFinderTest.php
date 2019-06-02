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
class FieldItemFinderTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $dataValueFactory;
	private $store;
	private $itemFetcher;
	private $printRequest;

	protected function setUp() {
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
			->will( $this->returnValue( 42 ) );

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
			->with($this->equalTo( PrintRequest::PRINT_THIS ) )
			->will( $this->returnValue( true ) );

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

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $dataItem ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_INST' ) ) )
			->will( $this->returnValue( [ $expected ] ) );

		$this->printRequest->expects( $this->at( 1 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_CATS ) )
			->will( $this->returnValue( true ) );

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
				$this->equalTo( $dataItem ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_INST' ) ) )
			->will( $this->returnValue( [ $expected ] ) );

		$this->printRequest->expects( $this->at( 2 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_CCAT ) )
			->will( $this->returnValue( true ) );

		$this->printRequest->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue( $expected ) );

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
				$this->equalTo( [ $dataItem ] ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( 'Prop' ) ),
				$this->anything() )
			->will( $this->returnValue( [ $expected ] ) );

		$this->printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_PROP ) )
			->will( $this->returnValue( true ) );

		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( false ) );

		$this->printRequest->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue( $this->dataValueFactory->newPropertyValueByLabel( 'Prop' ) ) );

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
				$this->equalTo( [ $dataItem ] ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( 'Prop' ) ),
				$this->anything() )
			->will( $this->returnValue( [ $expected ] ) );

		$this->printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_PROP ) )
			->will( $this->returnValue( true ) );

		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( false ) );

		$this->printRequest->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue(
				$this->dataValueFactory->newPropertyValueByLabel( 'Prop' ) ) );

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
				$this->equalTo( [ $dataItem ] ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( 'Prop' ) ),
				$this->anything() )
			->will( $this->returnValue( [ $expected ] ) );

		$this->printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_PROP ) )
			->will( $this->returnValue( true ) );

		$this->printRequest->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( false ) );

		$this->printRequest->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue( $propertyValue ) );

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
				$this->equalTo( [ $dataItem ] ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( 'Prop' ) ),
				$this->anything() )
			->will( $this->returnValue( [ $text ] ) );

		$this->printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_PROP ) )
			->will( $this->returnValue( true ) );

		$this->printRequest->expects( $this->any() )
			->method( 'getOutputFormat' )
			->will( $this->returnValue( '-raw' ) );

		$this->printRequest->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( false ) );

		$this->printRequest->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue( $propertyValue ) );

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
