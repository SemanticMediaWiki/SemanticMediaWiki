<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\SearchByProperty;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions;
use SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use stdClass;

/**
 * @covers \SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class QueryResultLookupTest extends TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			QueryResultLookup::class,
			new QueryResultLookup( $store )
		);
	}

	public function testDoQueryForNonValue() {
		$pageRequestOptions = new PageRequestOptions( 'Foo', [] );
		$pageRequestOptions->initialize();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->isType( 'null' ),
				$this->isInstanceOf( Property::class ),
				$this->anything() )
			->willReturn( [] );

		$instance = new QueryResultLookup( $store );

		$this->assertIsArray(

			$instance->doQuery( $pageRequestOptions )
		);
	}

	public function testDoQueryForExactValue() {
		$pageRequestOptions = new PageRequestOptions( 'Foo/Bar', [] );
		$pageRequestOptions->initialize();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->with(
				$this->isInstanceOf( Property::class ),
				$this->anything(),
				$this->anything() )
			->willReturn( [] );

		$instance = new QueryResultLookup( $store );

		$this->assertIsArray(
			$instance->doQuery( $pageRequestOptions )
		);
	}

	public function testDoQueryForNearbyResults() {
		$pageRequestOptions = new PageRequestOptions( 'Foo/Bar', [] );
		$pageRequestOptions->initialize();

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getNext' )
			->willReturn( false );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getQueryResult' )
			->with( $this->isInstanceOf( Query::class ) )
			->willReturn( $queryResult );

		$instance = new QueryResultLookup( $store );

		$this->assertIsArray(
			$instance->doQueryForNearbyResults( $pageRequestOptions, 1 )
		);
	}

	public function testDoQueryLinksReferences() {
		$idTable = $this->getMockBuilder( stdClass::class )
			->setMethods( [ 'getId' ] )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 42 );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$pageRequestOptions = new PageRequestOptions( 'Foo/Bar', [] );
		$pageRequestOptions->initialize();

		$instance = new QueryResultLookup( $store );

		$this->assertIsArray(
			$instance->doQueryLinksReferences( $pageRequestOptions, 1 )
		);
	}

}
