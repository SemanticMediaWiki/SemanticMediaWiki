<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\QueryEngineFactory;

/**
 * @covers \SMW\SQLStore\QueryEngineFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class QueryEngineFactoryTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
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
			'\SMW\SQLStore\QueryEngine\ConditionBuilder',
			$instance->newConditionBuilder()
		);
	}

	public function testCanConstructQuerySegmentListProcessor() {
		$instance = new QueryEngineFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QuerySegmentListProcessor',
			$instance->newQuerySegmentListProcessor()
		);
	}

	public function testCanConstructQueryEngine() {
		$instance = new QueryEngineFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QueryEngine',
			$instance->newQueryEngine()
		);
	}

	public function testCanConstructConceptQuerySegmentBuilder() {
		$instance = new QueryEngineFactory( $this->store );

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder',
			$instance->newConceptQuerySegmentBuilder()
		);
	}

}
