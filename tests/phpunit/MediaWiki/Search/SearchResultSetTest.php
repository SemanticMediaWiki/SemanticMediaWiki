<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\SearchResultSet;
use SMW\DIWikiPage;

/**
 * @covers \SMW\MediaWiki\Search\SearchResultSet
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Stephan Gambke
 */
class SearchResultSetTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var SearchResultSet The search result set under test
	 */
	protected $resultSet;
	private $queryResult;

	protected function setUp(): void {
		$queryToken = $this->getMockBuilder( '\SMW\Query\QueryToken' )
			->disableOriginalConstructor()
			->getMock();

		$queryToken->expects( $this->any() )
			->method( 'getTokens' )
			->willReturn( [] );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryToken' )
			->willReturn( $queryToken );

		$this->queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getQuery', 'getResults' ] )
			->getMock();

		$pageMock = $this->getMockBuilder( 'SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$pageMock->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( null );

		$this->queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$this->queryResult->expects( $this->any() )
			->method( 'getResults' )
			->willReturn( [ $pageMock, $pageMock, $pageMock ] );

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
			->willReturn( 'Foo ...' );

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
			->willReturn( [ 'Foo' => 1, 'Bar' => 2 ] );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryToken' )
			->willReturn( $queryToken );

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getQuery', 'getResults' ] )
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

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
			->willReturn( true );

		$page = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$queryToken = $this->getMockBuilder( '\SMW\Query\QueryToken' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryToken' )
			->willReturn( $queryToken );

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getResults' )
			->willReturn( [ $page ] );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$resultSet = new SearchResultSet( $queryResult, 42 );

		$this->assertInstanceOf(
			'\SearchSuggestionSet',
			$resultSet->newSearchSuggestionSet()
		);
	}

	public function testNewSearchSuggestionSet_FilterSameTitle() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$title->expects( $this->any() )
			->method( 'getPrefixedDBKey' )
			->willReturn( 'Foo' );

		$page_1 = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page_1->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$page_2 = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page_2->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$page_3 = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getResults' )
			->willReturn( [ $page_1, $page_2, $page_3 ] );

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$resultSet = new SearchResultSet( $queryResult, 42 );

		$searchSuggestionSet = $resultSet->newSearchSuggestionSet();

		$this->assertCount(
			1,
			$searchSuggestionSet->getSuggestions()
		);
	}

}
