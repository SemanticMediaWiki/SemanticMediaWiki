<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\TableFieldUpdater;

/**
 * @covers \SMW\SQLStore\TableFieldUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TableFieldUpdaterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			TableFieldUpdater::class,
			new TableFieldUpdater( $store )
		);
	}

	public function testIsEqualSortKey() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$collator = $this->getMockBuilder( '\SMW\Utils\Collator' )
			->disableOriginalConstructor()
			->getMock();

		$collator->expects( $this->exactly( 2 ) )
			->method( 'getSortKey' )
			->will( $this->returnValue( true ) );

		$instance = new TableFieldUpdater(
			$store,
			$collator
		);

		$this->assertTrue(
			$instance->isEqualByCollation( 'Foo', 'Foo' )
		);
	}

	public function testUpdateSearchAndSortField() {

		$collator = $this->getMockBuilder( '\SMW\Utils\Collator' )
			->disableOriginalConstructor()
			->getMock();

		$collator->expects( $this->once() )
			->method( 'getSortKey' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'update' )
				->with(
					$this->anything(),
					$this->anything(),
					$this->equalTo( array( 'smw_id' => 42 ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new TableFieldUpdater(
			$store,
			$collator
		);

		$instance->updateCollationField( 42, 'Foo' );
	}

}
