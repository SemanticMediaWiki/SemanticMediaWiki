<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\TableFieldUpdater;

/**
 * @covers \SMW\SQLStore\TableFieldUpdater
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TableFieldUpdaterTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			TableFieldUpdater::class,
			new TableFieldUpdater( $store )
		);
	}

	public function testUpdateSortField() {
		$collator = $this->getMockBuilder( '\SMW\MediaWiki\Collator' )
			->disableOriginalConstructor()
			->getMock();

		$collator->expects( $this->once() )
			->method( 'getSortKey' )
			->willReturn( 'Foo' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'timestamp' )
			->willReturn( '1970' );

		$connection->expects( $this->once() )
			->method( 'update' )
				->with(
					$this->anything(),
					$this->equalTo( [ 'smw_sortkey' => 'Foo', 'smw_sort' => 'Foo', 'smw_touched' => 1970 ] ),
					[ 'smw_id' => 42 ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new TableFieldUpdater(
			$store,
			$collator
		);

		$instance->updateSortField( 42, 'Foo' );
	}

	public function testUpdateRevField() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'timestamp' )
			->willReturn( '1970' );

		$connection->expects( $this->once() )
			->method( 'update' )
				->with(
					$this->anything(),
					$this->equalTo( [ 'smw_rev' => 1001, 'smw_touched' => 1970 ] ),
					[ 'smw_id'  => 42 ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new TableFieldUpdater(
			$store
		);

		$instance->updateRevField( 42, 1001 );
	}

	public function testUpdateTouchedField() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'timestamp' )
			->willReturn( '1970' );

		$connection->expects( $this->once() )
			->method( 'update' )
				->with(
					$this->anything(),
					[ 'smw_touched' => 1970 ],
					[ 'smw_id'  => 42 ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new TableFieldUpdater(
			$store
		);

		$instance->updateTouchedField( 42 );
	}

	public function testUpdateIwField() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'update' )
				->with(
					$this->anything(),
					$this->equalTo( [ 'smw_iw' => 'foo', 'smw_hash' => 'abc1234' ] ),
					[ 'smw_id'  => 42 ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new TableFieldUpdater(
			$store
		);

		$instance->updateIwField( 42, 'foo', 'abc1234' );
	}

}
