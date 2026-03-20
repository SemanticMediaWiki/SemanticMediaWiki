<?php

namespace SMW\Tests\Unit\SQLStore\TableBuilder\Examiner;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\IdBorder;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\IdBorder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class IdBorderTest extends TestCase {

	private $spyMessageReporter;
	private $store;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			IdBorder::class,
			new IdBorder( $this->store )
		);
	}

	public function testCheckBorder_HasBorder() {
		$row = [
			'smw_id' => 100
		];

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( [ (object)$row ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new IdBorder(
			$this->store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );

		$instance->check(
			[
				IdBorder::UPPER_BOUND  => 100,
				IdBorder::LEGACY_BOUND => 42
			]
		);

		$this->assertStringContainsString(
			'space for internal properties allocated',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testCheckBorder_HasMultipleBorders() {
		$rows = [
			(object)[ 'smw_id' => 100 ],
			(object)[ 'smw_id' => 9999 ]
		];

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( $rows );

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->anything(),
				[ 'smw_id' => 9999 ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new IdBorder(
			$this->store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );

		$instance->check(
			[
				IdBorder::UPPER_BOUND  => 100,
				IdBorder::LEGACY_BOUND => 42
			]
		);

		$this->assertStringContainsString(
			'space for internal properties allocated',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testCheckBorder_NoBorder() {
		$rows = [];

		$expected = [
			'smw_id' => 100,
			'smw_title' => '',
			'smw_namespace' => 0,
			'smw_iw' => ':smw-border',
			'smw_subobject' => '',
			'smw_sortkey' => '',
		];

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'moveSMWPageID' ] )
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( $rows );

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$expected );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new IdBorder(
			$this->store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );

		$instance->check(
			[
				IdBorder::UPPER_BOUND  => 100,
				IdBorder::LEGACY_BOUND => 42
			]
		);

		$this->assertStringContainsString(
			'allocating space for internal properties',
			$this->spyMessageReporter->getMessagesAsString()
		);

		$this->assertStringContainsString(
			'moving upper bound',
			$this->spyMessageReporter->getMessagesAsString()
		);

		$this->assertStringContainsString(
			'42 to 100',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testMissingUpperboundThrowsException() {
		$instance = new IdBorder(
			$this->store
		);

		$this->expectException( '\RuntimeException' );
		$instance->check();
	}

	public function testMissingLegacyboundThrowsException() {
		$instance = new IdBorder(
			$this->store
		);

		$this->expectException( '\RuntimeException' );

		$instance->check(
			[
				IdBorder::UPPER_BOUND  => 100,
			]
		);
	}

}
