<?php

namespace SMW\Tests\SQLStore\ChangeOp;

use SMW\SQLStore\ChangeOp\TableChangeOp;

/**
 * @covers \SMW\SQLStore\ChangeOp\TableChangeOp
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class TableChangeOpTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TableChangeOp::class,
			new TableChangeOp( 'foo', [] )
		);
	}

	public function testEmptyOps() {

		$diff = [];

		$instance = new TableChangeOp(
			'foo',
			$diff
		);

		$this->assertSame(
			'foo',
			$instance->getTableName()
		);

		$this->assertFalse(
			$instance->isFixedPropertyOp()
		);

		$this->assertFalse(
			$instance->hasChangeOp( TableChangeOp::OP_INSERT )
		);

		$this->assertNull(
			$instance->getFixedPropertyValueBy( 'key' )
		);

		$this->assertInternalType(
			'array',
			$instance->getFieldChangeOps( 'insert' )
		);
	}

	public function testFixedPropertyOps() {

		$diff = [
		'property' =>
			[
				'key' => '_MDAT',
				'p_id' => 29,
			],
		'insert' =>
			[
			0 =>
				[
					's_id' => 462,
					'o_serialized' => '1/2016/6/10/2/3/31/0',
					'o_sortkey' => '2457549.5857755',
				],
			],
		'delete' =>
			[
				0 =>
				[
				's_id' => 462,
				'o_serialized' => '1/2016/6/10/2/1/0/0',
				'o_sortkey' => '2457549.5840278',
				],
			],
		];

		$instance = new TableChangeOp(
			'foo',
			$diff
		);

		$this->assertSame(
			'foo',
			$instance->getTableName()
		);

		$this->assertTrue(
			$instance->isFixedPropertyOp()
		);

		$this->assertTrue(
			$instance->hasChangeOp( TableChangeOp::OP_INSERT )
		);

		$this->assertSame(
			'_MDAT',
			$instance->getFixedPropertyValueBy( 'key' )
		);
	}

	public function testGetFieldChangeOpsNoType() {

		$diff = [
		'property' =>
			[
				'key' => '_MDAT',
				'p_id' => 29,
			],
		'insert' =>
			[
			0 =>
				[
					's_id' => 462,
					'o_serialized' => '1/2016/6/10/2/3/31/0',
					'o_sortkey' => '2457549.5857755',
				],
			],
		'delete' =>
			[
				0 =>
				[
					's_id' => 462,
					'o_serialized' => '1/2016/6/10/2/1/0/0',
					'o_sortkey' => '2457549.5840278',
				],
				[
					's_id' => 42,
					'p_id' => 1001,
					'o_id' => 9999
				]
			]
		];

		$instance = new TableChangeOp(
			'foo',
			$diff
		);

		$this->assertCount(
			3,
			$instance->getFieldChangeOps()
		);

		$this->assertCount(
			1,
			$instance->getFieldChangeOps( TableChangeOp::OP_INSERT )
		);

		$this->assertCount(
			2,
			$instance->getFieldChangeOps( TableChangeOp::OP_DELETE )
		);

		$this->assertCount(
			2,
			$instance->getFieldChangeOps( null, [ 's_id' => [ 42 => true ] ] )
		);
	}

	public function testFieldChangeOps_WithNoOperation() {

		$diff = [
			[
				's_id' => 462,
				'o_serialized' => '1/2016/6/10/2/1/0/0',
				'o_sortkey' => '2457549.5840278',
			],
			[
				's_id' => 42,
				'p_id' => 1001,
				'o_id' => 9999
			]
		];

		$instance = new TableChangeOp(
			'foo',
			$diff
		);

		$this->assertCount(
			2,
			$instance->getFieldChangeOps()
		);

		$this->assertCount(
			1,
			$instance->getFieldChangeOps( null, [ 's_id' => [ 42 => true ] ] )
		);
	}

	public function testToArray() {

		$diff = [
			[
				's_id' => 462,
				'o_serialized' => '1/2016/6/10/2/1/0/0',
				'o_sortkey' => '2457549.5840278',
			],
			[
				's_id' => 42,
				'p_id' => 1001,
				'o_id' => 9999
			]
		];

		$instance = new TableChangeOp(
			'foo',
			$diff
		);

		$this->assertSame(
			['foo' => $diff ],
			$instance->toArray()
		);
	}

}
