<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\ExtendedSearch;
use SMW\Tests\TestEnvironment;
use SMWQuery;

/**
 * @covers \SMW\MediaWiki\Search\ExtendedSearch
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author Stephan Gambke
 */
class ExtendedSearchTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $store;
	private $fallbackSearchEngine;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->setMethods( [ 'replacePrefixes', 'searchTitle', 'searchText' ] )
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ExtendedSearch::class,
			new ExtendedSearch( $this->store, $this->fallbackSearchEngine )
		);
	}

	public function testFallbackSearchEngineAccessToPublicProperties() {
		$reflect = new \ReflectionObject( $this->fallbackSearchEngine );
		$properties = [ 'prefix', 'namespaces' ];

		foreach ( $reflect->getProperties( \ReflectionProperty::IS_PUBLIC ) as $prop ) {
			foreach ( $properties as $k => $p ) {
				if ( $prop->getName() === $p ) {
					unset( $properties[$k] );
				}
			}
		}

		$this->assertEmpty(
			$properties
		);
	}

	public function testSetLimitOffset() {
		$instance = new ExtendedSearch(
			$this->store,
			$this->fallbackSearchEngine
		);

		$instance->setLimitOffset( 10, false );

		$this->assertEquals(
			10,
			$instance->getLimit()
		);

		$this->assertSame(
			0,
			$instance->getOffset()
		);
	}

	public function testSearchTitle_withNonsemanticQuery() {
		$term = 'Some string that can not be interpreted as a semantic query';

		$searchResultSet = $this->getMockBuilder( 'SearchResultSet' )
			->disableOriginalConstructor()
			->getMock();

		$this->fallbackSearchEngine->expects( $this->once() )
			->method( 'replacePrefixes' )
			->willReturnArgument( 0 );

		$this->fallbackSearchEngine->expects( $this->once() )
			->method( 'searchTitle' )
			->willReturnMap( [ [ $term, $searchResultSet ] ] );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$instance = new ExtendedSearch(
			$this->store,
			$this->fallbackSearchEngine
		);

		$this->assertEquals(
			$searchResultSet,
			$instance->searchTitle( $term )
		);
	}

	public function testSearchTitle_withEmptyQuery() {
		$term = '   ';

		$searchResultSet = $this->getMockBuilder( 'SearchResultSet' )
			->disableOriginalConstructor()
			->getMock();

		$this->fallbackSearchEngine->expects( $this->once() )
			->method( 'replacePrefixes' )
			->willReturnArgument( 0 );

		$this->fallbackSearchEngine->expects( $this->once() )
			->method( 'searchTitle' )
			->willReturnMap( [ [ $term, $searchResultSet ] ] );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$instance = new ExtendedSearch(
			$this->store,
			$this->fallbackSearchEngine
		);

		$this->assertEquals(
			$searchResultSet,
			$instance->searchTitle( $term )
		);
	}

	public function testSearchText_withSemanticQuery() {
		$term = '[[Some string that can be interpreted as a semantic query]]';

		$infoLink = $this->getMockBuilder( '\SMWInfolink' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$queryResult->expects( $this->any() )
			->method( 'getQueryLink' )
			->willReturn( $infoLink );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$this->store->expects( $this->exactly( 2 ) )
			->method( 'getQueryResult' )
			->willReturnCallback( static function ( SMWQuery $query ) use ( $queryResult ) {
				return $query->querymode === SMWQuery::MODE_COUNT ? 9001 : $queryResult;
			} );

		$instance = new ExtendedSearch(
			$this->store,
			$this->fallbackSearchEngine
		);

		$result = $instance->searchText( $term );

		$this->assertInstanceOf(
			'SMW\MediaWiki\Search\SearchResultSet',
			$result
		);

		$this->assertEquals(
			9001,
			$result->getTotalHits()
		);
	}

	public function testSearchText_withNonsemanticQuery() {
		$term = 'Some string that can not be interpreted as a semantic query';

		$searchResultSet = $this->getMockBuilder( 'SearchResultSet' )
			->disableOriginalConstructor()
			->getMock();

		$this->fallbackSearchEngine->expects( $this->once() )
			->method( 'replacePrefixes' )
			->willReturnArgument( 0 );

		$this->fallbackSearchEngine->expects( $this->once() )
			->method( 'searchText' )
			->willReturnMap( [ [ $term, $searchResultSet ] ] );

		$instance = new ExtendedSearch(
			$this->store,
			$this->fallbackSearchEngine
		);

		$this->assertEquals(
			$searchResultSet,
			$instance->searchText( $term )
		);
	}

	public function testSearchTitle_withSemanticQuery() {
		$term = '[[Some string that can be interpreted as a semantic query]]';

		$instance = new ExtendedSearch(
			$this->store,
			$this->fallbackSearchEngine
		);

		$this->assertNull(
			$instance->searchTitle( $term )
		);
	}

	public function testCompletionSearch_OnEligiblePrefix() {
		$infoLink = $this->getMockBuilder( '\SMWInfolink' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$queryBuilder = $this->getMockBuilder( '\SMW\MediaWiki\Search\QueryBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$queryBuilder->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$queryResult->expects( $this->any() )
			->method( 'getResults' )
			->willReturn( [] );

		$queryResult->expects( $this->any() )
			->method( 'getQueryLink' )
			->willReturn( $infoLink );

		$this->store->expects( $this->exactly( 3 ) )
			->method( 'getQueryResult' )
			->willReturnCallback( static function ( SMWQuery $query ) use ( $queryResult ) {
				return $query->querymode === \SMWQuery::MODE_COUNT ? 9001 : $queryResult;
			} );

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'completionSearch' )
			->with( 'fcat:Foo' );

		$instance = new ExtendedSearch(
			$this->store,
			$fallbackSearchEngine
		);

		$instance->setExtraPrefixMap(
			[ 'abc', 'cat' => 'bar' ]
		);

		$instance->setQueryBuilder(
			$queryBuilder
		);

		$this->assertInstanceof(
			'\SearchSuggestionSet',
			$instance->completionSearch( 'in:Foo' )
		);

		$instance->completionSearch( 'abc:Foo' );
		$instance->completionSearch( 'cat:Foo' );

		// In-between doesn't count so it uses the fallbacksearch engine
		$instance->completionSearch( 'fcat:Foo' );
	}

	public function testCompletionSearch_NoRelevantPrefix() {
		$searchSuggestionSet = $this->getMockBuilder( '\SearchSuggestionSet' )
			->disableOriginalConstructor()
			->getMock();

		$searchSuggestionSet->expects( $this->any() )
			->method( 'map' )
			->willReturn( [] );

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'completionSearch' )
			->willReturn( $searchSuggestionSet );

		$instance = new ExtendedSearch(
			$this->store,
			$fallbackSearchEngine
		);

		$this->assertInstanceof(
			'\SearchSuggestionSet',
			$instance->completionSearch( 'Foo' )
		);
	}

	public function testCompletionSearch_NoRelevantPrefix_ReplaceEmptySearchTermWithInfusedTerm() {
		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'completionSearch' )
			->with( 'origTerm' );

		$instance = new ExtendedSearch(
			$this->store,
			$fallbackSearchEngine
		);

		$instance->setCompletionSearchTerm( 'origTerm' );
		$instance->completionSearch( '' );
	}

}
