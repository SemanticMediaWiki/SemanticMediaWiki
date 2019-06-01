<?php

namespace SMW\Tests\Query\Result;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\Query\PrintRequest;
use SMW\Query\Result\ResultFieldMatchFinder;

/**
 * @covers SMW\Query\Result\ResultFieldMatchFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ResultFieldMatchFinderTest extends \PHPUnit_Framework_TestCase {

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
			ResultFieldMatchFinder::class,
			new ResultFieldMatchFinder( $this->store, $this->itemFetcher, $this->printRequest )
		);
	}

	public function testGetRequestOptions() {

		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( 42 ) );

		$instance = new ResultFieldMatchFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertInstanceOf(
			'SMW\RequestOptions',
			$instance->getRequestOptions()
		);
	}

	public function testFindAndMatch_THIS() {

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo' );

		$this->printRequest->expects( $this->any() )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_THIS ) )
			->will( $this->returnValue( true ) );

		$instance = new ResultFieldMatchFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $dataItem ],
			$instance->findAndMatch( $dataItem )
		);
	}

	public function testFindAndMatch_CATS() {

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

		$instance = new ResultFieldMatchFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findAndMatch( $dataItem )
		);
	}

	public function testFindAndMatch_CCAT() {

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

		$instance = new ResultFieldMatchFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $this->dataItemFactory->newDIBoolean( true ) ],
			$instance->findAndMatch( $dataItem )
		);
	}

	public function testFindAndMatch_PROP() {

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

		$instance = new ResultFieldMatchFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findAndMatch( $dataItem )
		);
	}

	public function testFindAndMatchWithIteratorAsValueResultOnPRINT_PROP() {

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

		$instance = new ResultFieldMatchFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findAndMatch( $dataItem )
		);
	}

	public function testFindAndMatchWithBlobValueResultAndRemovedLink() {

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

		$instance = new ResultFieldMatchFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findAndMatch( $dataItem )
		);
	}

	public function testFindAndMatchWithBlobValueResultAndRetainedLink() {

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

		$instance = new ResultFieldMatchFinder(
			$this->store,
			$this->itemFetcher,
			$this->printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findAndMatch( $dataItem )
		);
	}

}
