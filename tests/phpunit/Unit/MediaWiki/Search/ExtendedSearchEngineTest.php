<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\ExtendedSearchEngine;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;
use SMWQuery;

/**
 * @covers \SMW\MediaWiki\Search\ExtendedSearchEngine
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Stephan Gambke
 */
class ExtendedSearchEngineTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $connection;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();

		$this->connection = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ExtendedSearchEngine::class,
			new ExtendedSearchEngine()
		);
	}

	public function testGetDefaultFallbackSearchEngineForNullFallbackSearchType() {

		$searchEngine = 'SearchDatabase';

		if ( class_exists( 'SearchEngine' ) ) {

			$reflection = new \ReflectionClass( 'SearchEngine' );

			if ( $reflection->isInstantiable() ) {
				$searchEngine = 'SearchEngine';
			}
		}

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSearchEngine' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getSearchEngine' )
			->will( $this->returnValue( $searchEngine ) );

		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', null );

		$searchEngine = new ExtendedSearchEngine(
			$connection
		);

		$this->assertInstanceOf(
			'SearchEngine',
			$searchEngine->getFallbackSearchEngine()
		);
	}

	public function testSetGetFallbackSearchEngine() {

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );

		$this->assertEquals(
			$fallbackSearchEngine,
			$searchEngine->getFallbackSearchEngine()
		);
	}

	public function testSupports() {

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'supports')
			->with( $this->equalTo( 'Some feature' ) )
			->will( $this->returnValueMap( [ [ 'Some feature', true ] ] ) );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );

		$this->assertTrue(
			$searchEngine->supports( 'Some feature' )
		);
	}

	public function testNormalizeText() {

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'normalizeText')
			->with( $this->equalTo( 'Some text' ) )
			->will( $this->returnValueMap( [ [ 'Some text', 'Some normalized text' ] ] ) );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );

		$this->assertEquals(
			'Some normalized text',
			$searchEngine->normalizeText( 'Some text' )
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

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'getTextFromContent')
			->with(
				$this->equalTo( $title ),
				$this->equalTo( $content ) )
			->will( $this->returnValueMap( [ [ $title, $content, 'text from content for title' ] ] ) );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );

		$this->assertEquals(
			'text from content for title',
			$searchEngine->getTextFromContent( $title, $content )
		);
	}

	public function testTextAlreadyUpdatedForIndex() {

		if ( ! method_exists( 'SearchEngine', 'textAlreadyUpdatedForIndex' ) ) {
			$this->markTestSkipped( 'SearchEngine::textAlreadyUpdatedForIndex() is undefined. Probably not yet present in the tested MW version.' );
		}

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'textAlreadyUpdatedForIndex')
			->with()
			->will( $this->returnValue( true ) );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );

		$this->assertTrue(
			$searchEngine->textAlreadyUpdatedForIndex( 'Some text' )
		);
	}

	public function testUpdate() {

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'update')
			->with(
				$this->equalTo( 42 ),
				$this->equalTo( 'Some title' ),
				$this->equalTo( 'Some text' ) );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );
		$searchEngine->update( 42, 'Some title', 'Some text' );
	}

	public function testUpdateTitle() {

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'updateTitle')
			->with(
				$this->equalTo( 42 ),
				$this->equalTo( 'Some title' ) );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );
		$searchEngine->updateTitle( 42, 'Some title' );
	}

	public function testDelete() {

		if ( ! method_exists( 'SearchEngine', 'delete' ) ) {
			$this->markTestSkipped( 'SearchEngine::delete() is undefined. Probably not yet present in the tested MW version.' );
		}

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'delete')
			->with(
				$this->equalTo( 42 ),
				$this->equalTo( 'Some title' ) );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );
		$searchEngine->delete( 42, 'Some title' );
	}

	public function testSetFeatureData() {

		if ( ! method_exists( 'SearchEngine', 'delete' ) ) {
			$this->markTestSkipped( 'SearchEngine::delete() is undefined. Probably not yet present in the tested MW version.' );
		}

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'setFeatureData')
			->with(
				$this->equalTo( 'Some feature name' ),
				$this->equalTo( 'Some feature expression' ) );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );
		$searchEngine->setFeatureData( 'Some feature name', 'Some feature expression' );

		$this->assertEquals(
			'Some feature expression',
			$searchEngine->getFeatureData( 'Some feature name' )
		);

		$this->assertNull(
			$searchEngine->getFeatureData( 'Some non-existent feature name' )
		);
	}

	public function testReplacePrefixes() {

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$this->assertEquals(
			'Some query',
			$searchEngine->replacePrefixes( 'Some query' )
		);
	}

	public function testTransformSearchTerm() {

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$this->assertEquals(
			'Some query',
			$searchEngine->transformSearchTerm( 'Some query' )
		);
	}

	public function testSetLimitOffset() {

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'setLimitOffset')
			->with(
				$this->equalTo( 9001 ),
				$this->equalTo( 42 ) );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );

		$searchEngine->setLimitOffset( 9001, 42 );

		$this->assertEquals(
			9001,
			$searchEngine->getLimit()
		);

		$this->assertEquals(
			42,
			$searchEngine->getOffset()
		);
	}

	public function testSetNamespaces() {

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'setNamespaces')
			->with( $this->equalTo( [ 1, 2, 3, 5, 8 ] ) );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );
		$searchEngine->setNamespaces( [ 1, 2, 3, 5, 8 ] );

		$this->assertEquals(
			[ 1, 2, 3, 5, 8 ],
			$searchEngine->namespaces
		);
	}

	public function testSetShowSuggestion() {

		if ( ! method_exists( 'SearchEngine', 'setShowSuggestion' ) ) {
			$this->markTestSkipped( 'SearchEngine::setShowSuggestion() is undefined. Probably not yet present in the tested MW version.' );
		}

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'setShowSuggestion')
			->with( $this->equalTo( true ) );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );
		$searchEngine->setShowSuggestion( true );

		$this->assertTrue(
			$searchEngine->getShowSuggestion()
		);
	}

	public function testCompletionSearch_OnEligiblePrefix() {

		$searchSuggestionSet = $this->getMockBuilder( '\SearchSuggestionSet' )
			->disableOriginalConstructor()
			->getMock();

		$searchSuggestionSet->expects( $this->any() )
			->method( 'map')
			->will( $this->returnValue( [] ) );

		$extendedSearch = $this->getMockBuilder( '\SMW\MediaWiki\Search\ExtendedSearch' )
			->disableOriginalConstructor()
			->getMock();

		$extendedSearch->expects( $this->once() )
			->method( 'completionSearch' )
			->with( $this->equalTo( 'in:Foo' ) )
			->will( $this->returnValue( $searchSuggestionSet ) );

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setExtendedSearch( $extendedSearch );
		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );

		$this->assertInstanceof(
			'\SearchSuggestionSet',
			$searchEngine->completionSearch( 'in:Foo' )
		);
	}

	public function tesCompletionSearch_NoRelevantPrefix() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$searchSuggestionSet = $this->getMockBuilder( '\SearchSuggestionSet' )
			->disableOriginalConstructor()
			->getMock();

		$searchSuggestionSet->expects( $this->any() )
			->method( 'map')
			->will( $this->returnValue( [] ) );

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'setShowSuggestion')
			->with( $this->equalTo( true ) );

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'completionSearch' )
			->will( $this->returnValue( $searchSuggestionSet ) );

		$extendedSearch = $this->getMockBuilder( '\SMW\MediaWiki\Search\ExtendedSearch' )
			->disableOriginalConstructor()
			->getMock();

		$extendedSearch->setFallbackSearchEngine( $fallbackSearchEngine );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setExtendedSearch( $extendedSearch );
		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );
		$searchEngine->setShowSuggestion( true );

		$this->assertInstanceof(
			'\SearchSuggestionSet',
			$searchEngine->completionSearch( 'Foo' )
		);
	}

}
