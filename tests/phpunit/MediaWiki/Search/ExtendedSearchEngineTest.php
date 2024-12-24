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
class ExtendedSearchEngineTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $connection;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	protected function tearDown(): void {
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

		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getSearchEngine' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getSearchEngine' )
			->willReturn( $searchEngine );

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
			->method( 'supports' )
			->with( 'Some feature' )
			->willReturnMap( [ [ 'Some feature', true ] ] );

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
			->method( 'normalizeText' )
			->with( 'Some text' )
			->willReturnMap( [ [ 'Some text', 'Some normalized text' ] ] );

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
		if ( !method_exists( 'SearchEngine', 'getTextFromContent' ) ) {
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
			->method( 'getTextFromContent' )
			->with(
				$title,
				$content )
			->willReturnMap( [ [ $title, $content, 'text from content for title' ] ] );

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
		if ( !method_exists( 'SearchEngine', 'textAlreadyUpdatedForIndex' ) ) {
			$this->markTestSkipped( 'SearchEngine::textAlreadyUpdatedForIndex() is undefined. Probably not yet present in the tested MW version.' );
		}

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'textAlreadyUpdatedForIndex' )
			->with()
			->willReturn( true );

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
			->method( 'update' )
			->with(
				42,
				'Some title',
				'Some text' );

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
			->method( 'updateTitle' )
			->with(
				42,
				'Some title' );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );
		$searchEngine->updateTitle( 42, 'Some title' );
	}

	public function testDelete() {
		if ( !method_exists( 'SearchEngine', 'delete' ) ) {
			$this->markTestSkipped( 'SearchEngine::delete() is undefined. Probably not yet present in the tested MW version.' );
		}

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'delete' )
			->with(
				42,
				'Some title' );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setFallbackSearchEngine( $fallbackSearchEngine );
		$searchEngine->delete( 42, 'Some title' );
	}

	public function testSetFeatureData() {
		if ( !method_exists( 'SearchEngine', 'delete' ) ) {
			$this->markTestSkipped( 'SearchEngine::delete() is undefined. Probably not yet present in the tested MW version.' );
		}

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'setFeatureData' )
			->with(
				'Some feature name',
				'Some feature expression' );

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
			->method( 'setLimitOffset' )
			->with(
				9001,
				42 );

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
			->method( 'setNamespaces' )
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
		if ( !method_exists( 'SearchEngine', 'setShowSuggestion' ) ) {
			$this->markTestSkipped( 'SearchEngine::setShowSuggestion() is undefined. Probably not yet present in the tested MW version.' );
		}

		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$fallbackSearchEngine->expects( $this->once() )
			->method( 'setShowSuggestion' )
			->with( true );

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
			->method( 'map' )
			->willReturn( [] );

		$extendedSearch = $this->getMockBuilder( '\SMW\MediaWiki\Search\ExtendedSearch' )
			->disableOriginalConstructor()
			->getMock();

		$extendedSearch->expects( $this->once() )
			->method( 'completionSearch' )
			->with( 'in:Foo' )
			->willReturn( $searchSuggestionSet );

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

	public function testCompletionSearch_NoRelevantPrefix() {
		$searchSuggestionSet = $this->getMockBuilder( '\SearchSuggestionSet' )
			->disableOriginalConstructor()
			->getMock();

		$searchSuggestionSet->expects( $this->any() )
			->method( 'map' )
			->willReturn( [] );

		$extendedSearch = $this->getMockBuilder( '\SMW\MediaWiki\Search\ExtendedSearch' )
			->disableOriginalConstructor()
			->getMock();

		$extendedSearch->expects( $this->once() )
			->method( 'completionSearch' )
			->willReturn( $searchSuggestionSet );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setExtendedSearch( $extendedSearch );
		$searchEngine->setShowSuggestion( true );

		$this->assertInstanceof(
			'\SearchSuggestionSet',
			$searchEngine->completionSearch( 'Foo' )
		);
	}

	public function testCompletionSearchWithVariants() {
		$searchSuggestionSet = $this->getMockBuilder( '\SearchSuggestionSet' )
			->disableOriginalConstructor()
			->getMock();

		$searchSuggestionSet->expects( $this->any() )
			->method( 'map' )
			->willReturn( [] );

		$extendedSearch = $this->getMockBuilder( '\SMW\MediaWiki\Search\ExtendedSearch' )
			->disableOriginalConstructor()
			->getMock();

		$extendedSearch->expects( $this->once() )
			->method( 'completionSearch' )
			->willReturn( $searchSuggestionSet );

		$extendedSearch->expects( $this->once() )
			->method( 'setCompletionSearchTerm' )
			->with( 'Foo_Variants' );

		$searchEngine = new ExtendedSearchEngine(
			$this->connection
		);

		$searchEngine->setExtendedSearch( $extendedSearch );

		$searchEngine->completionSearchWithVariants( 'Foo_Variants' );
	}

}
