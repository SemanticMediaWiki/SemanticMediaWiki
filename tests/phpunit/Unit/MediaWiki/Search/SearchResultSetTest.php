<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\SearchResultSet;

/**
 * @covers  \SMW\MediaWiki\Search\SearchResultSet
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

	protected function setUp() {

		$queryResultMock = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$pageMock = $this->getMockBuilder( 'SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$pageMock->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( null ) );

		$queryResultMock->expects( $this->any() )
			->method( 'getResults' )
			->will( $this->returnValue( array( $pageMock, $pageMock, $pageMock ) ) );

		$this->resultSet = new SearchResultSet( $queryResultMock, 42 );

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

	public function testSearchContainedSyntax() {
		$this->assertTrue( $this->resultSet->searchContainedSyntax() );
	}

	public function testGetTotalHits() {
		$this->assertEquals( 42, $this->resultSet->getTotalHits() );
	}

}
