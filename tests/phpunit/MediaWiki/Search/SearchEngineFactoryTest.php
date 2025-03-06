<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\SearchEngineFactory;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Search\SearchEngineFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author Stephan Gambke
 */
class SearchEngineFactoryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $connection;
	private $param;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		if ( version_compare( MW_VERSION, '1.41', '>=' ) ) {
			$this->param = '\Wikimedia\Rdbms\IConnectionProvider';
		} else {
			$this->param = '\Wikimedia\Rdbms\Database';
		}

		$this->connection = $this->getMockBuilder( $this->param )
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

		$reflection = new \ReflectionClass( 'SearchEngine' );

		if ( $reflection->isInstantiable() ) {
			$searchEngine = 'SearchEngine';
		}

		if ( version_compare( MW_VERSION, '1.41', '>=' ) ) {
			$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\IConnectionProvider' )
				->disableOriginalConstructor()
				->setMethods( [ 'getSearchEngine' ] )
				->getMockForAbstractClass();
		} else {
			$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
				->disableOriginalConstructor()
				->setMethods( [ 'getSearchEngine' ] )
				->getMockForAbstractClass();
		}

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
			return new \stdClass;
		};

		$this->testEnvironment->addConfiguration( 'smwgFallbackSearchType', $callback );

		$searchEngineFactory = new SearchEngineFactory();

		$this->expectException( '\SMW\MediaWiki\Search\Exception\SearchEngineInvalidTypeException' );
		$searchEngineFactory->newFallbackSearchEngine( $this->connection );
	}

	public function testNewFallbackSearchEngine_ConstructFromString() {
		if ( version_compare( MW_VERSION, '1.41', '>=' ) ) {
			$this->markTestSkipped( 'Check assertions for MW 1.41 and higher versions.' );
		}
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
