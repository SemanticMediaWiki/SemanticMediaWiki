<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\SearchResultSet;

/**
 * @covers \SMW\MediaWiki\Search\SearchResultSet
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Stephan Gambke
 */
class SearchResultSetTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var SearchResultSet The search result set under test
	 */
	protected $resultSet;
	private $queryResult;

	protected function setUp() {

		$queryToken = $this->getMockBuilder( '\SMW\Query\QueryToken' )
			->disableOriginalConstructor()
			->getMock();

		$queryToken->expects( $this->any() )
			->method( 'getTokens' )
			->will( $this->returnValue( [] ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryToken' )
			->will( $this->returnValue( $queryToken ) );

		$this->queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->setMethods( [ 'getQuery', 'getResults' ] )
			->getMock();

		$pageMock = $this->getMockBuilder( 'SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$pageMock->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( null ) );

		$this->queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$this->queryResult->expects( $this->any() )
			->method( 'getResults' )
			->will( $this->returnValue( [ $pageMock, $pageMock, $pageMock ] ) );

		$this->resultSet = new SearchResultSet( $this->queryResult, 42 );

	}

	public function testCanConstruct() {
		$this->assertInstanceOf( '\SMW\MediaWiki\Search\SearchResultSet', $this->resultSet );
	}

	public function testNumRows() {
		$this->assertEquals( 3, $this->resultSet->numRows() );
	}

	public function testHasResults() {
		$this->assertGreaterThan( 0, $this->resultSet->hasResults() );
	}

	public function testNext() {
		$this->assertInstanceOf( 'SearchResult', $this->resultSet->next() );
		$this->assertInstanceOf( 'SearchResult', $this->resultSet->next() );
		$this->assertInstanceOf( 'SearchResult', $this->resultSet->next() );
		$this->assertFalse( $this->resultSet->next() );
	}

	public function testExtractResults() {

		$res = $this->resultSet->extractResults();

		$this->assertCount(
			3,
			$res
		);

		$this->assertEquals(
			3,
			$this->resultSet->numRows()
		);

		foreach ( $res as $searchResult ) {
			$this->assertInstanceOf(
				'SearchResult',
				 $searchResult
			);
		}
	}

	public function testSearchContainedSyntax() {
		$this->assertTrue( $this->resultSet->searchContainedSyntax() );
	}

	public function testGetTotalHits() {
		$this->assertEquals( 42, $this->resultSet->getTotalHits() );
	}

	public function testExcerpt() {

		$excerpts = $this->getMockBuilder( 'SMW\Query\Excerpts' )
			->disableOriginalConstructor()
			->getMock();

		$excerpts->expects( $this->any() )
			->method( 'getExcerpt' )
			->will( $this->returnValue( 'Foo ...' ) );

		$this->queryResult->setExcerpts( $excerpts );

		$resultSet = new SearchResultSet( $this->queryResult, 42 );
		$searchResult = $resultSet->next();

		$this->assertEquals(
			'Foo ...',
			$searchResult->getExcerpt()
		);
	}

	public function testTermMatches() {

		$queryToken = $this->getMockBuilder( '\SMW\Query\QueryToken' )
			->disableOriginalConstructor()
			->getMock();

		$queryToken->expects( $this->any() )
			->method( 'getTokens' )
			->will( $this->returnValue( [ 'Foo' => 1, 'Bar' => 2 ] ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryToken' )
			->will( $this->returnValue( $queryToken ) );

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->setMethods( [ 'getQuery', 'getResults' ] )
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$resultSet = new SearchResultSet( $queryResult, 42 );

		$this->assertEquals(
			[ '\bFoo\b', '\bBar\b' ],
			$resultSet->termMatches()
		);
	}

	public function testNewSearchSuggestionSet() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$page = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$queryToken = $this->getMockBuilder( '\SMW\Query\QueryToken' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryToken' )
			->will( $this->returnValue( $queryToken ) );

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getResults' )
			->will( $this->returnValue( [ $page ] ) );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$resultSet = new SearchResultSet( $queryResult, 42 );

		$this->assertInstanceOf(
			'\SearchSuggestionSet',
			$resultSet->newSearchSuggestionSet()
		);
	}

}
