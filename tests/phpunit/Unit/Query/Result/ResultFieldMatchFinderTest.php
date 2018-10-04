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

	protected function setUp() {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\Query\Result\ResultFieldMatchFinder',
			new ResultFieldMatchFinder( $store, $printRequest )
		);
	}

	public function testGetRequestOptions() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( 42 ) );

		$instance = new ResultFieldMatchFinder(
			$store,
			$printRequest
		);

		$this->assertInstanceOf(
			'SMW\RequestOptions',
			$instance->getRequestOptions()
		);
	}

	public function testFindAndMatch_THIS() {

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->any() )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_THIS ) )
			->will( $this->returnValue( true ) );

		$instance = new ResultFieldMatchFinder(
			$store,
			$printRequest
		);

		$this->assertEquals(
			[ $dataItem ],
			$instance->findAndMatch( $dataItem )
		);
	}

	public function testFindAndMatch_CATS() {

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo' );
		$expected = $this->dataItemFactory->newDIWikiPage( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $dataItem ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_INST' ) ) )
			->will( $this->returnValue( [ $expected ] ) );

		$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->at( 1 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_CATS ) )
			->will( $this->returnValue( true ) );

		$instance = new ResultFieldMatchFinder(
			$store,
			$printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findAndMatch( $dataItem )
		);
	}

	public function testFindAndMatch_CCAT() {

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar' );
		$expected = $this->dataItemFactory->newDIWikiPage( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $dataItem ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_INST' ) ) )
			->will( $this->returnValue( [ $expected ] ) );

		$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->at( 2 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_CCAT ) )
			->will( $this->returnValue( true ) );

		$printRequest->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue( $expected ) );

		$instance = new ResultFieldMatchFinder(
			$store,
			$printRequest
		);

		$this->assertEquals(
			[ $this->dataItemFactory->newDIBoolean( true ) ],
			$instance->findAndMatch( $dataItem )
		);
	}

	public function testFindAndMatch_PROP() {

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar' );
		$expected = $this->dataItemFactory->newDIWikiPage( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $dataItem ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( 'Prop' ) ) )
			->will( $this->returnValue( [ $expected ] ) );

		$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_PROP ) )
			->will( $this->returnValue( true ) );

		$printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( false ) );

		$printRequest->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue( $this->dataValueFactory->newPropertyValueByLabel( 'Prop' ) ) );

		$instance = new ResultFieldMatchFinder(
			$store,
			$printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findAndMatch( $dataItem )
		);
	}

	public function testFindAndMatchWithIteratorAsValueResultOnPRINT_PROP() {

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar' );
		$expected = $this->dataItemFactory->newDIWikiPage( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		// #2541, return an iterator
		$store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $dataItem ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( 'Prop' ) ) )
			->will( $this->returnValue( new \ArrayIterator( [ $expected ] ) ) );

		$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_PROP ) )
			->will( $this->returnValue( true ) );

		$printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( false ) );

		$printRequest->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue(
				$this->dataValueFactory->newPropertyValueByLabel( 'Prop' ) ) );

		$instance = new ResultFieldMatchFinder(
			$store,
			$printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findAndMatch( $dataItem )
		);
	}

	public function testFindAndMatchWithBlobValueResultAndRemovedLink() {

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar' );
		$text = $this->dataItemFactory->newDIBlob( '[[Foo::bar]]' );
		$expected = $this->dataItemFactory->newDIBlob( 'bar' );

		$propertyValue = $this->dataValueFactory->newPropertyValueByLabel( 'Prop' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		// #2541, return an iterator
		$store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $dataItem ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( 'Prop' ) ) )
			->will( $this->returnValue( new \ArrayIterator( [ $text ] ) ) );

		$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_PROP ) )
			->will( $this->returnValue( true ) );

		$printRequest->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( false ) );

		$printRequest->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue( $propertyValue ) );

		$instance = new ResultFieldMatchFinder(
			$store,
			$printRequest
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

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		// #2541, return an iterator
		$store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $dataItem ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( 'Prop' ) ) )
			->will( $this->returnValue( new \ArrayIterator( [ $text ] ) ) );

		$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->at( 3 ) )
			->method( 'isMode' )
			->with($this->equalTo( PrintRequest::PRINT_PROP ) )
			->will( $this->returnValue( true ) );

		$printRequest->expects( $this->any() )
			->method( 'getOutputFormat' )
			->will( $this->returnValue( '-raw' ) );

		$printRequest->expects( $this->any() )
			->method( 'getTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->will( $this->returnValue( false ) );

		$printRequest->expects( $this->once() )
			->method( 'getData' )
			->will( $this->returnValue( $propertyValue ) );

		$instance = new ResultFieldMatchFinder(
			$store,
			$printRequest
		);

		$this->assertEquals(
			[ $expected ],
			$instance->findAndMatch( $dataItem )
		);
	}

}
