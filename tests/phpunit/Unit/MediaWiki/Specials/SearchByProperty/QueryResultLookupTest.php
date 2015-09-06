<?php

namespace SMW\Tests\MediaWiki\Specials\SearchByProperty;

use SMW\Tests\Utils\Validators\ValidatorFactory;
use SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup;
use SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions;

/**
 * @covers \SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class QueryResultLookupTest extends \PHPUnit_Framework_TestCase {

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

		$pageRequestOptions = new PageRequestOptions( 'Foo', array() );
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
			->will( $this->returnValue( array() ) );

		$instance = new QueryResultLookup( $store );

		$this->assertInternaltype(
			'array',
			$instance->doQuery( $pageRequestOptions )
		);
	}

	public function testDoQueryForExactValue() {

		$pageRequestOptions = new PageRequestOptions( 'Foo/Bar', array() );
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
			->will( $this->returnValue( array() ) );

		$instance = new QueryResultLookup( $store );

		$this->assertInternaltype(
			'array',
			$instance->doQuery( $pageRequestOptions )
		);
	}

	public function testDoQueryForNearbyResults() {

		$pageRequestOptions = new PageRequestOptions( 'Foo/Bar', array() );
		$pageRequestOptions->initialize();

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getNext' )
			->will( $this->returnValue( false ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getQueryResult' )
			->with( $this->isInstanceOf( '\SMWQuery' ) )
			->will( $this->returnValue( $queryResult ) );

		$instance = new QueryResultLookup( $store );

		$this->assertInternaltype(
			'array',
			$instance->doQueryForNearbyResults( $pageRequestOptions, 1 )
		);
	}

}
