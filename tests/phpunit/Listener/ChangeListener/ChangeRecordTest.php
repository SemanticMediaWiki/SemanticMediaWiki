<?php

namespace SMW\Tests\Listener\ChangeListener;

use SMW\Listener\ChangeListener\ChangeRecord;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Listener\ChangeListener\ChangeRecord
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ChangeRecordTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ChangeRecord::class,
			new ChangeRecord()
		);
	}

	public function testGet() {

		$changeRecord = new ChangeRecord(
			[
				's_id' => 1000,
				'p_id' => 2000,
				'o_id' => 42
			]
		);

		$this->assertEquals(
			42,
			$changeRecord->get( 'o_id' )
		);
	}

	public function testGetFromInnerRecord() {

		$changeRecord = new ChangeRecord(
			[
				new ChangeRecord( [ 's_id' => 42 ] ),
				new ChangeRecord( [ 's_id' => 1001 ] ),
				new ChangeRecord( [ 's_id' => 9000 ] ),
			]
		);

		$this->assertEquals(
			1001,
			$changeRecord->get( 1 )->get( 's_id' )
		);
	}

	public function testGetFromInnerMultiRecord() {

		$changeRecord = new ChangeRecord(
			[
				new ChangeRecord( [ 'row' => [ 's_id' => 42 ] ] ),
				new ChangeRecord( [ 'row' => [ 's_id' => 1001 ] ] ),
				new ChangeRecord( [ 'row' => [ 's_id' => 9000 ] ] ),
			]
		);

		$this->assertEquals(
			1001,
			$changeRecord->get( 1 )->get( 'row.s_id' )
		);
	}

	public function testIterateOuterRecord() {

		$changeRecord = new ChangeRecord(
			[
				new ChangeRecord( [ 's_id' => 42 ] )
			]
		);

		foreach ( $changeRecord as $record ) {
			$this->assertEquals(
				42,
				$record->get( 's_id' )
			);
		}
	}

	public function testHas_NonExistingKey() {

		$changeRecord = new ChangeRecord();

		$this->assertFalse(
			$changeRecord->has( 'foo' )
		);
	}

	public function testGetOnNonExistingKey_ThrowsException() {

		$changeRecord = new ChangeRecord();

		$this->expectException( '\RuntimeException' );
		$changeRecord->get( 'foo' );
	}

}
