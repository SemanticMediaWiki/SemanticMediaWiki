<?php

namespace SMW\Tests\Query\Result;

use SMW\DataItemFactory;
use SMW\Query\Result\ItemFetcher;

/**
 * @covers SMW\Query\Result\ItemFetcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ItemFetcherTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $store;
	private $requestOptions;

	protected function setUp() {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'service', 'getPropertyValues' ] )
			->getMockForAbstractClass();

		$this->requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ItemFetcher::class,
			new ItemFetcher( $this->store )
		);
	}

	public function testHighlightTokens() {

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo' );

		$instance = new ItemFetcher(
			$this->store
		);

		$this->assertEquals(
			$dataItem,
			$instance->highlightTokens( $dataItem )
		);
	}

	public function testFetchFromLegacy() {

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo' );
		$property = $this->dataItemFactory->newDIProperty( 'Bar' );

		$expected = [
			$this->dataItemFactory->newDIWikiPage( 'Foobar' )
		];

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $dataItem ),
				$this->equalTo( $property ) )
			->will( $this->returnValue( $expected ) );

		$instance = new ItemFetcher(
			$this->store
		);

		$instance->setPrefetchFlag( false );

		$this->assertEquals(
			$expected,
			$instance->fetch( [ $dataItem ], $property, $this->requestOptions )
		);
	}

	public function testFetchFromPrefetchCache() {

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo' );
		$property = $this->dataItemFactory->newDIProperty( 'Bar' );

		$expected = [
			$this->dataItemFactory->newDIWikiPage( 'Foobar' )
		];

		$prefetchCache = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\PrefetchCache' )
			->disableOriginalConstructor()
			->getMock();

		$prefetchCache->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $dataItem ),
				$this->equalTo( $property ) )
			->will( $this->returnValue( $expected ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'service' )
			->with( $this->equalTo( 'PrefetchCache' ) )
			->will( $this->returnValue( $prefetchCache ) );

		$instance = new ItemFetcher(
			$this->store
		);

		$instance->setPrefetchFlag( SMW_QUERYRESULT_PREFETCH );
		$this->requestOptions->isChain = false;

		$this->assertEquals(
			$expected,
			$instance->fetch( [ $dataItem ], $property, $this->requestOptions )
		);
	}

}
