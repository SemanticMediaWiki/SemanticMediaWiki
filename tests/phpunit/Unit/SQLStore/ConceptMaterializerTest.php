<?php

namespace SMW\Tests\Unit\SQLStore;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\ConceptMaterializer;
use SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\ConceptMaterializer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class ConceptMaterializerTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $store;
	private $conceptQuerySegmentBuilder;

	protected function setUp(): void {
		parent::setUp();

		$this->conceptQuerySegmentBuilder = $this->getMockBuilder( ConceptQuerySegmentBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConceptMaterializer::class,
			new ConceptMaterializer( $this->store, $this->conceptQuerySegmentBuilder )
		);
	}

	public function testRefreshConceptCache() {
		$this->conceptQuerySegmentBuilder->expects( $this->once() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new ConceptMaterializer(
			new SQLStore(),
			$this->conceptQuerySegmentBuilder
		);

		$instance->refreshConceptCache(
			Title::newFromText( 'Foo', SMW_NS_CONCEPT )
		);
	}

	public function testDeleteConceptCache() {
		$capturedDeleteTables = [];
		$capturedDeleteWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder(
			$capturedDeleteTables,
			$capturedDeleteWheres
		);

		$capturedUpdateTables = [];
		$capturedUpdateSets = [];
		$capturedUpdateWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedUpdateTables,
			$capturedUpdateSets,
			$capturedUpdateWheres
		);

		$selectBuilder = $this->createMockSelectQueryBuilder( [] );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'newDeleteQueryBuilder' )->willReturn( $deleteBuilder );
		$connection->method( 'newUpdateQueryBuilder' )->willReturn( $updateBuilder );
		$connection->method( 'newSelectQueryBuilder' )->willReturn( $selectBuilder );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = new SQLStore();
		$store->setConnectionManager( $connectionManager );

		$instance = new ConceptMaterializer(
			$store,
			$this->conceptQuerySegmentBuilder
		);

		$instance->deleteConceptCache(
			Title::newFromText( 'Foo', SMW_NS_CONCEPT )
		);

		$this->assertSame( [ SQLStore::CONCEPT_CACHE_TABLE ], $capturedDeleteTables );
		$this->assertSame( [ 'smw_fpt_conc' ], $capturedUpdateTables );
		$this->assertSame(
			[ [ 'cache_date' => null, 'cache_count' => null ] ],
			$capturedUpdateSets
		);
	}

}
