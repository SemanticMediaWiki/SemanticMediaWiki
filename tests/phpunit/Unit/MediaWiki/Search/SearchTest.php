<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\Search;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;
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

	use PHPUnitCompat;

	private $testEnvironment;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
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

		$searchEngine = 'SearchDatabase';

		if ( class_exists( 'SearchEngine' ) ) {

			$reflection = new \ReflectionClass( 'SearchEngine' );

			if ( $reflection->isInstantiable() ) {
				$searchEngine = 'SearchEngine';
			}
		}

		$databaseBase = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSearchEngine' ] )
			->getMockForAbstractClass();

		$databaseBase->expects( $this->any() )
			->method( 'getSearchEngine' )
			->will( $this->returnValue( $searchEngine ) );

		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', null );

		$search = new Search();
		$search->setDB( $databaseBase );

		$this->assertInstanceOf(
			'SearchEngine',
			$search->getFallbackSearchEngine()
		);
	}

	public function testInvalidFallbackSearchEngineThrowsException() {

		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', 'InvalidFallbackSearchEngine' );

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
			->will( $this->returnValueMap( [ [ $term, $searchResultSet ] ] ) );

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
			->will( $this->returnValueMap( [ [ $term, $searchResultSet ] ] ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$this->assertEquals(
			$searchResultSet,
			$search->searchTitle( $term )
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
			->will( $this->returnValue( $query ) );

		$queryResult->expects( $this->any() )
			->method( 'getQueryLink' )
			->will( $this->returnValue( $infoLink ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->exactly( 2 ) )
			->method( 'getQueryResult' )
			->will( $this->returnCallback( function ( SMWQuery $query ) use ( $queryResult ) {
				return $query->querymode === SMWQuery::MODE_COUNT ? 9001 : $queryResult;
			} ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$search = new Search();
		$result = $search->searchText( $term );

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
			->will( $this->returnValueMap( [ [ $term, $searchResultSet ] ] ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$this->assertEquals(
			$searchResultSet,
			$search->searchText( $term )
		);
	}

	public function testSearchTitle_withSemanticQuery() {

		$term = '[[Some string that can be interpreted as a semantic query]]';

		$search = new Search();

		$this->assertNull( $search->searchTitle( $term ) );
	}

	public function testSupports() {

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'supports')
			->with( $this->equalTo( 'Some feature' ) )
			->will( $this->returnValueMap( [ [ 'Some feature', true ] ] ) );

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
			->will( $this->returnValueMap( [ [ 'Some text', 'Some normalized text' ] ] ) );

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
			->will( $this->returnValueMap( [ [ $title, $content, 'text from content for title' ] ] ) );

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
			->with( $this->equalTo( [ 1, 2, 3, 5, 8 ] ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );
		$search->setNamespaces( [ 1, 2, 3, 5, 8 ] );

		$this->assertEquals(
			[ 1, 2, 3, 5, 8 ],
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

	public function testCompletionSearch_OnEligiblePrefix() {

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
			->will( $this->returnValue( $query ) );

		$queryResult->expects( $this->any() )
			->method( 'getQueryLink' )
			->will( $this->returnValue( $infoLink ) );

		$queryResult->expects( $this->any() )
			->method( 'getResults' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );

		$this->assertInstanceof(
			'\SearchSuggestionSet',
			$search->completionSearch( 'in:Foo' )
		);
	}

	public function testCompletionSearch_NoRelevantPrefix() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$searchSuggestionSet = $this->getMockBuilder( '\SearchSuggestionSet' )
			->disableOriginalConstructor()
			->getMock();

		$searchSuggestionSet->expects( $this->any() )
			->method( 'map')
			->will( $this->returnValue( [] ) );

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine->expects( $this->once() )
			->method( 'setShowSuggestion')
			->with( $this->equalTo( true ) );

		$searchEngine->expects( $this->once() )
			->method( 'completionSearch' )
			->will( $this->returnValue( $searchSuggestionSet ) );

		$search = new Search();
		$search->setFallbackSearchEngine( $searchEngine );
		$search->setShowSuggestion( true );

		$this->assertInstanceof(
			'\SearchSuggestionSet',
			$search->completionSearch( 'Foo' )
		);
	}

}
