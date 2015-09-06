<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Search\Search;
use SMWQuery;

/**
 * @covers \SMW\MediaWiki\Search\Search
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Stephan Gambke
 */
class SearchTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		ApplicationFactory::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Search\Search',
			new Search()
		);
	}

	public function testGetDefaultDBConnection() {

		$search = new Search();

		$this->assertInstanceOf(
			'DatabaseBase',
			$search->getDB()
		);
	}

	public function testSetGetDBConnection() {

		$dbMock = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$search = new Search();
		$search->setDB( $dbMock );

		$this->assertEquals( $dbMock, $search->getDB() );
	}

	public function testGetDefaultFallbackSearchEngineForNullFallbackSearchType() {

		$dbMock = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'getSearchEngine' ) )
			->getMockForAbstractClass();

		$dbMock->expects( $this->once() )
			->method( 'getSearchEngine' )
			->will( $this->returnValue( 'SearchEngine' ) );

		ApplicationFactory::getInstance()->getSettings()->set( 'smwgFallbackSearchType', null );

		$search = new Search();
		$search->setDB( $dbMock );

		$this->assertInstanceOf(
			'SearchEngine',
			$search->getFallbackSearchEngine()
		);
	}

	public function testInvalidFallbackSearchEngineThrowsException() {

		ApplicationFactory::getInstance()->getSettings()->set( 'smwgFallbackSearchType', 'InvalidFallbackSearchEngine' );

		$search = new Search();

		$this->setExpectedException( 'RuntimeException' );
		$search->getFallbackSearchEngine();
	}

	public function testSetGetFallbackSearchEngine() {

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$this->assertEquals(
			$searchEngine,
			$search->getFallbackSearchEngine()
		);
	}

	public function testSearchTitle_withNonsemanticQuery() {

		$term = 'Some string that can not be interpreted as a semantic query';

		$searchResultSet = $this->getMockBuilder( 'SearchResultSet' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'transformSearchTerm' )
			->will( $this->returnArgument( 0 ) );

		$searchEngine->expects( $this->once() )
			->method( 'replacePrefixes' )
			->will( $this->returnArgument( 0 ) );

		$searchEngine->expects( $this->once() )
			->method( 'searchTitle')
			->will( $this->returnValueMap( array( array( $term, $searchResultSet ) ) ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$this->assertEquals(
			$searchResultSet,
			$search->searchTitle( $term )
		);
	}

	public function testSearchTitle_withEmptyQuery() {

		$term = '   ';

		$searchResultSet = $this->getMockBuilder( 'SearchResultSet' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'transformSearchTerm' )
			->will( $this->returnArgument( 0 ) );

		$searchEngine->expects( $this->once() )
			->method( 'replacePrefixes' )
			->will( $this->returnArgument( 0 ) );

		$searchEngine->expects( $this->once() )
			->method( 'searchTitle')
			->will( $this->returnValueMap( array( array( $term, $searchResultSet ) ) ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$this->assertEquals(
			$searchResultSet,
			$search->searchTitle( $term )
		);
	}

	public function testSearchTitle_withSemanticQuery() {

		$term = '[[Some string that can be interpreted as a semantic query]]';

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->exactly( 2 ) )
			->method( 'getQueryResult' )
			->will( $this->returnCallback( function ( SMWQuery $query ) use ( $queryResult ) {
				return $query->querymode === SMWQuery::MODE_COUNT ? 9001 : $queryResult;
			} ) );

		ApplicationFactory::getInstance()->registerObject( 'Store', $store );

		$search = new Search();
		$result = $search->searchTitle( $term );

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

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'transformSearchTerm' )
			->will( $this->returnArgument( 0 ) );

		$searchEngine->expects( $this->once() )
			->method( 'replacePrefixes' )
			->will( $this->returnArgument( 0 ) );

		$searchEngine->expects( $this->once() )
			->method( 'searchText')
			->will( $this->returnValueMap( array( array( $term, $searchResultSet ) ) ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$this->assertEquals(
			$searchResultSet,
			$search->searchText( $term )
		);
	}

	public function testSearchText_withSemanticQuery() {

		$term = '[[Some string that can be interpreted as a semantic query]]';

		$search = new Search();

		$this->assertNull( $search->searchText( $term ) );
	}

	public function testSupports() {

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'supports')
			->with( $this->equalTo( 'Some feature' ) )
			->will( $this->returnValueMap( array( array( 'Some feature', true ) ) ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$this->assertTrue( $search->supports( 'Some feature' ) );
	}

	public function testNormalizeText() {

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'normalizeText')
			->with( $this->equalTo( 'Some text' ) )
			->will( $this->returnValueMap( array( array( 'Some text', 'Some normalized text' ) ) ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$this->assertEquals(
			'Some normalized text',
			$search->normalizeText( 'Some text' )
		);
	}

	public function testGetTextFromContent() {

		if ( ! method_exists( 'SearchEngine', 'getTextFromContent' ) ) {
			$this->markTestSkipped( 'SearchEngine::getTextFromContent() is undefined. Probably not yet present in the tested MW version.' );
		}

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$content = $this->getMockBuilder( 'Content' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'getTextFromContent')
			->with(
				$this->equalTo( $title ),
				$this->equalTo( $content ) )
			->will( $this->returnValueMap( array( array( $title, $content, 'text from content for title' ) ) ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$this->assertEquals(
			'text from content for title',
			$search->getTextFromContent( $title, $content )
		);
	}


	public function testTextAlreadyUpdatedForIndex() {

		if ( ! method_exists( 'SearchEngine', 'textAlreadyUpdatedForIndex' ) ) {
			$this->markTestSkipped( 'SearchEngine::textAlreadyUpdatedForIndex() is undefined. Probably not yet present in the tested MW version.' );
		}

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'textAlreadyUpdatedForIndex')
			->with()
			->will( $this->returnValue( true ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$this->assertTrue( $search->textAlreadyUpdatedForIndex( 'Some text' ) );
	}

	public function testUpdate() {

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'update')
			->with(
				$this->equalTo( 42 ),
				$this->equalTo( 'Some title' ),
				$this->equalTo( 'Some text' ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$search->update( 42, 'Some title', 'Some text' );
	}

	public function testUpdateTitle() {

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'updateTitle')
			->with(
				$this->equalTo( 42 ),
				$this->equalTo( 'Some title' ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$search->updateTitle( 42, 'Some title' );
	}

	public function testDelete() {

		if ( ! method_exists( 'SearchEngine', 'delete' ) ) {
			$this->markTestSkipped( 'SearchEngine::delete() is undefined. Probably not yet present in the tested MW version.' );
		}

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'delete')
			->with(
				$this->equalTo( 42 ),
				$this->equalTo( 'Some title' ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$search->delete( 42, 'Some title' );
	}

	public function testSetFeatureData() {

		if ( ! method_exists( 'SearchEngine', 'delete' ) ) {
			$this->markTestSkipped( 'SearchEngine::delete() is undefined. Probably not yet present in the tested MW version.' );
		}

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'setFeatureData')
			->with(
				$this->equalTo( 'Some feature name' ),
				$this->equalTo( 'Some feature expression' ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );
		$search->setFeatureData( 'Some feature name', 'Some feature expression' );

		$this->assertEquals(
			'Some feature expression',
			$search->getFeatureData( 'Some feature name' )
		);

		$this->assertNull( $search->getFeatureData( 'Some non-existent feature name' ) );
	}

	public function testReplacePrefixes() {

		$search = new Search();

		$this->assertEquals(
			'Some query',
			$search->replacePrefixes( 'Some query' )
		);
	}

	public function testTransformSearchTerm() {

		$search = new Search();

		$this->assertEquals(
			'Some query',
			$search->transformSearchTerm( 'Some query' )
		);
	}

	public function testSetLimitOffset() {

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'setLimitOffset')
			->with(
				$this->equalTo( 9001 ),
				$this->equalTo( 42 ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$search->setLimitOffset( 9001, 42 );

		$this->assertEquals( 9001, $search->getLimit() );
		$this->assertEquals( 42, $search->getOffset() );
	}

	public function testSetNamespaces() {

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'setNamespaces')
			->with( $this->equalTo( array( 1, 2, 3, 5, 8 ) ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );
		$search->setNamespaces( array( 1, 2, 3, 5, 8 ) );

		$this->assertEquals(
			array( 1, 2, 3, 5, 8 ),
			$search->namespaces
		);
	}

	public function testSetShowSuggestion() {

		if ( ! method_exists( 'SearchEngine', 'setShowSuggestion' ) ) {
			$this->markTestSkipped( 'SearchEngine::setShowSuggestion() is undefined. Probably not yet present in the tested MW version.' );
		}

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'setShowSuggestion')
			->with( $this->equalTo( true ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );
		$search->setShowSuggestion( true );

		$this->assertTrue( $search->getShowSuggestion() );
	}

}
