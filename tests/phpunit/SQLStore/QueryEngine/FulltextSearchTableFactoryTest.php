<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\QueryEngine\Fulltext\MySQLValueMatchConditionBuilder;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTable;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater;
use SMW\SQLStore\QueryEngine\Fulltext\TextChangeUpdater;
use SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer;
use SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\QueryEngine\FulltextSearchTableFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableFactoryTest extends TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstructValueMatchConditionBuilderOnUnknownConnectionType() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			ValueMatchConditionBuilder::class,
			$instance->newValueMatchConditionBuilderByType( $this->store )
		);
	}

	public function testCanConstructValueMatchConditionBuilderOnMySQLConnectionType() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'getType' )
			->willReturn( 'mysql' );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			MySQLValueMatchConditionBuilder::class,
			$instance->newValueMatchConditionBuilderByType( $this->store )
		);
	}

	public function testCanConstructTextSanitizer() {
		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			TextSanitizer::class,
			$instance->newTextSanitizer()
		);
	}

	public function testCanConstructSearchTable() {
		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			SearchTable::class,
			$instance->newSearchTable( $this->store )
		);
	}

	public function testCanConstructSearchTableUpdater() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			SearchTableUpdater::class,
			$instance->newSearchTableUpdater( $this->store )
		);
	}

	public function testCanConstructTextChangeUpdater() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			TextChangeUpdater::class,
			$instance->newTextChangeUpdater( $this->store )
		);
	}

	public function testCanConstructSearchTableRebuilder() {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			SearchTableRebuilder::class,
			$instance->newSearchTableRebuilder( $this->store )
		);
	}

}
