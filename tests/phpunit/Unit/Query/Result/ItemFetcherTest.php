<?php

namespace SMW\Tests\Unit\Query\Result;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\Query\PrintRequest;
use SMW\Query\QueryToken;
use SMW\Query\Result\ItemFetcher;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\PrefetchCache;
use SMW\Store;

/**
 * @covers \SMW\Query\Result\ItemFetcher
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ItemFetcherTest extends TestCase {

	private $dataItemFactory;
	private $store;
	private $requestOptions;

	protected function setUp(): void {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'service', 'getPropertyValues' ] )
			->getMockForAbstractClass();

		$this->requestOptions = $this->getMockBuilder( RequestOptions::class )
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

	public function testHighlightTokens_Blob() {
		$queryToken = $this->getMockBuilder( QueryToken::class )
			->disableOriginalConstructor()
			->getMock();

		$queryToken->expects( $this->any() )
			->method( 'highlight' )
			->willReturn( '<b>Foo</b>' );

		$printRequest = $this->getMockBuilder( PrintRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->any() )
			->method( 'getTypeID' )
			->willReturn( '_txt' );

		$printRequest->expects( $this->any() )
			->method( 'getOutputFormat' )
			->willReturn( '' );

		$dataItem = $this->dataItemFactory->newDIBlob( 'Foo' );

		$instance = new ItemFetcher(
			$this->store
		);

		$instance->setPrintRequest(
			$printRequest
		);

		$instance->setQueryToken(
			$queryToken
		);

		$this->assertEquals(
			'<b>Foo</b>',
			$instance->highlightTokens( $dataItem )->getString()
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
				$dataItem,
				$property )
			->willReturn( $expected );

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

		$prefetchCache = $this->getMockBuilder( PrefetchCache::class )
			->disableOriginalConstructor()
			->getMock();

		$prefetchCache->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$dataItem,
				$property )
			->willReturn( $expected );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'service' )
			->with( 'PrefetchCache' )
			->willReturn( $prefetchCache );

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
