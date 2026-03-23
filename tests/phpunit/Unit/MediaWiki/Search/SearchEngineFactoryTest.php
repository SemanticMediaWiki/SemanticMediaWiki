<?php

namespace SMW\Tests\Unit\MediaWiki\Search;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SMW\Exception\ClassNotFoundException;
use SMW\MediaWiki\Search\Exception\SearchDatabaseInvalidTypeException;
use SMW\MediaWiki\Search\Exception\SearchEngineInvalidTypeException;
use SMW\MediaWiki\Search\ExtendedSearch;
use SMW\MediaWiki\Search\SearchEngineFactory;
use SMW\Tests\Fixtures\MediaWiki\Search\DummySearchDatabase;
use SMW\Tests\Fixtures\MediaWiki\Search\DummySearchEngine;
use SMW\Tests\TestEnvironment;
use stdClass;
use Wikimedia\Rdbms\IConnectionProvider;

/**
 * @covers \SMW\MediaWiki\Search\SearchEngineFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author Stephan Gambke
 */
class SearchEngineFactoryTest extends TestCase {

	private $testEnvironment;
	private $connection;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->connection = $this->getMockBuilder( IConnectionProvider::class )
		->disableOriginalConstructor()
		->getMockForAbstractClass();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SearchEngineFactory::class,
			new SearchEngineFactory()
		);
	}

	public function testGetFallbackSearchEngine_ConstructFromCallable() {
		$searchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$searchEngineFactory = new SearchEngineFactory();

		$this->assertInstanceOf(
			ExtendedSearch::class,
			$searchEngineFactory->newExtendedSearch( $searchEngine )
		);
	}

	public function testNewDefaultFallbackSearchEngineForNullFallbackSearchType() {
		$searchEngine = 'SearchDatabase';

		$reflection = new ReflectionClass( 'SearchEngine' );

		if ( $reflection->isInstantiable() ) {
			$searchEngine = 'SearchEngine';
		}

		$connection = $this->getMockBuilder( IConnectionProvider::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getSearchEngine' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->any() )
			->method( 'getSearchEngine' )
			->willReturn( $searchEngine );

		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', null );

		$searchEngineFactory = new SearchEngineFactory();

		$this->assertInstanceOf(
			'SearchEngine',
			$searchEngineFactory->newFallbackSearchEngine( $connection )
		);
	}

	public function testInvalidFallbackSearchEngineThrowsException() {
		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', 'InvalidFallbackSearchEngine' );

		$searchEngineFactory = new SearchEngineFactory();

		$this->expectException( 'RuntimeException' );
		$searchEngineFactory->newFallbackSearchEngine( $this->connection );
	}

	public function testNewFallbackSearchEngine_ConstructFromCallable() {
		$fallbackSearchEngine = $this->getMockBuilder( 'SearchEngine' )
			->disableOriginalConstructor()
			->getMock();

		$callback = static function () use( $fallbackSearchEngine ) {
			return $fallbackSearchEngine;
		};

		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', $callback );

		$searchEngineFactory = new SearchEngineFactory();

		$this->assertEquals(
			$fallbackSearchEngine,
			$searchEngineFactory->newFallbackSearchEngine( $this->connection )
		);
	}

	public function testNewFallbackSearchEngine_ConstructFromInvalidCallableThrowsException() {
		$callback = static function () {
			return new stdClass;
		};

		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', $callback );

		$searchEngineFactory = new SearchEngineFactory();

		$this->expectException( SearchEngineInvalidTypeException::class );
		$searchEngineFactory->newFallbackSearchEngine( $this->connection );
	}

	public function testNewFallbackSearchEngine_ConstructFromString() {
		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', DummySearchDatabase::class );

		$searchEngineFactory = new SearchEngineFactory();

		$this->assertInstanceOf(
			'\SearchDatabase',
			$searchEngineFactory->newFallbackSearchEngine( $this->connection )
		);
	}

	public function testNewFallbackSearchEngine_ConstructFromStringNonSearchDatabaseThrowsException() {
		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', DummySearchEngine::class );

		$searchEngineFactory = new SearchEngineFactory();

		$this->expectException( SearchDatabaseInvalidTypeException::class );
		$searchEngineFactory->newFallbackSearchEngine( $this->connection );
	}

	public function testNewFallbackSearchEngine_ConstructFromStringInvalidClassThrowsException() {
		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', 'ClassDoesntExist' );

		$searchEngineFactory = new SearchEngineFactory();

		$this->expectException( ClassNotFoundException::class );
		$searchEngineFactory->newFallbackSearchEngine( $this->connection );
	}

}
