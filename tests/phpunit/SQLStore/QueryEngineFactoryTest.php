<?php

namespace SMW\Tests\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\QueryEngine\QueryEngine;
use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;
use SMW\SQLStore\QueryEngineFactory;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\QueryEngineFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class QueryEngineFactoryTest extends TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			QueryEngineFactory::class,
			new QueryEngineFactory( $this->store )
		);
	}

	public function testCanConstructConditionBuilder() {
		$instance = new QueryEngineFactory( $this->store );

		$this->assertInstanceOf(
			ConditionBuilder::class,
			$instance->newConditionBuilder()
		);
	}

	public function testCanConstructQuerySegmentListProcessor() {
		$instance = new QueryEngineFactory( $this->store );

		$this->assertInstanceOf(
			QuerySegmentListProcessor::class,
			$instance->newQuerySegmentListProcessor()
		);
	}

	public function testCanConstructQueryEngine() {
		$instance = new QueryEngineFactory( $this->store );

		$this->assertInstanceOf(
			QueryEngine::class,
			$instance->newQueryEngine()
		);
	}

	public function testCanConstructConceptQuerySegmentBuilder() {
		$instance = new QueryEngineFactory( $this->store );

		$this->assertInstanceOf(
			ConceptQuerySegmentBuilder::class,
			$instance->newConceptQuerySegmentBuilder()
		);
	}

}
