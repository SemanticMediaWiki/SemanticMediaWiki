<?php

namespace SMW\Tests\MediaWiki\Specials\SearchByProperty;

use SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions;
use SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class QueryResultLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup',
			new QueryResultLookup( $store )
		);
	}

	public function testDoQueryForNonValue() {
		$pageRequestOptions = new PageRequestOptions( 'Foo', [] );
		$pageRequestOptions->initialize();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->isType( 'null' ),
				$this->isInstanceOf( '\SMW\DIProperty' ),
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

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->with(
				$this->isInstanceOf( '\SMW\DIProperty' ),
				$this->anything(),
				$this->anything() )
			->willReturn( [] );

		$instance = new QueryResultLookup( $store );

		$this->assertInternaltype(
			'array',
			$instance->doQuery( $pageRequestOptions )
		);
	}

	public function testDoQueryForNearbyResults() {
		$pageRequestOptions = new PageRequestOptions( 'Foo/Bar', [] );
		$pageRequestOptions->initialize();

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getNext' )
			->willReturn( false );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getQueryResult' )
			->with( $this->isInstanceOf( '\SMWQuery' ) )
			->willReturn( $queryResult );

		$instance = new QueryResultLookup( $store );

		$this->assertInternaltype(
			'array',
			$instance->doQueryForNearbyResults( $pageRequestOptions, 1 )
		);
	}

	public function testDoQueryLinksReferences() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'getId' ] )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 42 );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$pageRequestOptions = new PageRequestOptions( 'Foo/Bar', [] );
		$pageRequestOptions->initialize();

		$instance = new QueryResultLookup( $store );

		$this->assertInternaltype(
			'array',
			$instance->doQueryLinksReferences( $pageRequestOptions, 1 )
		);
	}

}
