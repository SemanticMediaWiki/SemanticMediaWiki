<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\SearchEngineFactory;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;
use SMWQuery;

/**
 * @covers \SMW\MediaWiki\Search\SearchEngineFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author Stephan Gambke
 */
class SearchEngineFactoryTest extends \PHPUnit\Framework\TestCase {

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
			'\SMW\MediaWiki\Search\ExtendedSearch',
			$searchEngineFactory->newExtendedSearch( $searchEngine )
		);
	}

	public function testNewDefaultFallbackSearchEngineForNullFallbackSearchType() {
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

		$callback = function () use( $fallbackSearchEngine ) {
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
		$callback = function () {
			return new \stdClass;
		};

		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', $callback );

		$searchEngineFactory = new SearchEngineFactory();

		$this->expectException( '\SMW\MediaWiki\Search\Exception\SearchEngineInvalidTypeException' );
		$searchEngineFactory->newFallbackSearchEngine( $this->connection );
	}

	public function testNewFallbackSearchEngine_ConstructFromString() {
		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', '\SMW\Tests\Fixtures\MediaWiki\Search\DummySearchDatabase' );

		$searchEngineFactory = new SearchEngineFactory();

		$this->assertInstanceOf(
			'\SearchDatabase',
			$searchEngineFactory->newFallbackSearchEngine( $this->connection )
		);
	}

	public function testNewFallbackSearchEngine_ConstructFromStringNonSearchDatabaseThrowsException() {
		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', '\SMW\Tests\Fixtures\MediaWiki\Search\DummySearchEngine' );

		$searchEngineFactory = new SearchEngineFactory();

		$this->expectException( '\SMW\MediaWiki\Search\Exception\SearchDatabaseInvalidTypeException' );
		$searchEngineFactory->newFallbackSearchEngine( $this->connection );
	}

	public function testNewFallbackSearchEngine_ConstructFromStringInvalidClassThrowsException() {
		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', 'ClassDoesntExist' );

		$searchEngineFactory = new SearchEngineFactory();

		$this->expectException( '\SMW\Exception\ClassNotFoundException' );
		$searchEngineFactory->newFallbackSearchEngine( $this->connection );
	}

}
