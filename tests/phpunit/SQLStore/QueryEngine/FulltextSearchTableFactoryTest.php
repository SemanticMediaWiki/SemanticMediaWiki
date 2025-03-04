<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;

/**
 * @covers \SMW\SQLStore\QueryEngine\FulltextSearchTableFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableFactoryTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstructValueMatchConditionBuilderOnUnknownConnectionType() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder',
			$instance->newValueMatchConditionBuilderByType( $this->store )
		);
	}

	public function testCanConstructValueMatchConditionBuilderOnMySQLConnectionType() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
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
			'\SMW\SQLStore\QueryEngine\Fulltext\MySQLValueMatchConditionBuilder',
			$instance->newValueMatchConditionBuilderByType( $this->store )
		);
	}

	public function testCanConstructTextSanitizer() {
		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer',
			$instance->newTextSanitizer()
		);
	}

	public function testCanConstructSearchTable() {
		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\SearchTable',
			$instance->newSearchTable( $this->store )
		);
	}

	public function testCanConstructSearchTableUpdater() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater',
			$instance->newSearchTableUpdater( $this->store )
		);
	}

	public function testCanConstructTextChangeUpdater() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\TextChangeUpdater',
			$instance->newTextChangeUpdater( $this->store )
		);
	}

	public function testCanConstructSearchTableRebuilder() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new FulltextSearchTableFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder',
			$instance->newSearchTableRebuilder( $this->store )
		);
	}

}
