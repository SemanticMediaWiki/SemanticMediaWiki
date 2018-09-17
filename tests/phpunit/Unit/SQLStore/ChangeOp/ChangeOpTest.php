<?php

namespace SMW\Tests\SQLStore\ChangeOp;

use SMW\DIWikiPage;
use SMW\SQLStore\ChangeOp\ChangeOp;

/**
 * @covers \SMW\SQLStore\ChangeOp\ChangeOp
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ChangeOpTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ChangeOp::class,
			new ChangeOp()
		);
	}

	public function testSerialize() {

		$instance = new ChangeOp(
			DIWikiPage::newFromText( __METHOD__ ),
			[]
		);

		$data = serialize( $instance );

		$this->assertInstanceOf(
			ChangeOp::class,
			unserialize( $data )
		);
	}

	/**
	 * @dataProvider diffDataProvider
	 */
	public function testDiff( $diff, $fixedPropertyRecord, $expectedOrdered, $expectedList ) {

		$instance = new ChangeOp(
			DIWikiPage::newFromText( __METHOD__ ),
			$diff
		);

		$instance->addFixedPropertyRecord(
			$fixedPropertyRecord[0],
			$fixedPropertyRecord[1]
		);

		$this->assertInternalType(
			'array',
			$instance->getFixedPropertyRecords()
		);

		$this->assertEquals(
			$expectedOrdered,
			$instance->getOrderedDiffByTable()
		);

		$this->assertEquals(
			$expectedList,
			$instance->getChangedEntityIdSummaryList()
		);
	}

	public function testGetTableChangeOps() {

		$diff = [
			0 => [
				'insert' => [
					'smw_di_number' => [
						0 => [
							's_id' => 3668,
							'p_id' => 61,
							'o_serialized' => '123',
							'o_sortkey' => '123',
						],
						1 => [
							's_id' => 3668,
							'p_id' => 62,
							'o_serialized' => '1234',
							'o_sortkey' => '1234',
						],
					],
					'smw_fpt_mdat' => [
						0 => [
							's_id' => 3668,
							'o_serialized' => '1/2015/8/16/9/28/39',
							'o_sortkey' => '2457250.8948958',
						],
					],
				],
				'delete' => [
					'smw_di_number' => [],
					'smw_fpt_mdat'  => [],
				]
			]
		];

		$instance = new ChangeOp(
			DIWikiPage::newFromText( __METHOD__ ),
			$diff
		);

		$this->assertCount(
			1,
			$instance->getTableChangeOps( 'smw_di_number' )
		);

		$this->assertInternalType(
			'array',
			$instance->getTableChangeOps()
		);
	}

	public function testGetListOfChangedEntitiesByType() {

		$diff = [
			0 => [
				'insert' => [
					'smw_di_number' => [
						0 => [
							's_id' => 3668,
							'p_id' => 61,
							'o_serialized' => '123',
							'o_sortkey' => '123',
						],
						1 => [
							's_id' => 3668,
							'p_id' => 62,
							'o_serialized' => '1234',
							'o_sortkey' => '1234',
						],
					],
					'smw_fpt_mdat' => [
						0 => [
							's_id' => 3668,
							'o_serialized' => '1/2015/8/16/9/28/39',
							'o_sortkey' => '2457250.8948958',
						],
					],
				],
				'delete' => [
					'smw_di_number' => [],
					'smw_fpt_mdat'  => [
						0 => [
							's_id' => 3667,
							'o_serialized' => '1/2015/8/16/9/28/39',
							'o_sortkey' => '2457250.8948958',
						]
					],
				]
			]
		];

		$instance = new ChangeOp(
			DIWikiPage::newFromText( __METHOD__ ),
			$diff
		);

		$this->assertEquals(
			[
				3667 => true
			],
			$instance->getChangedEntityIdListByType( $instance::OP_DELETE )
		);

		$this->assertEquals(
			[
				61 => true,
				3668 => true,
				62 => true
			],
			$instance->getChangedEntityIdListByType( $instance::OP_INSERT )
		);
	}

	public function testTryToGetTableChangeOpForSingleTable() {

		$diff = [];

		$instance = new ChangeOp(
			DIWikiPage::newFromText( __METHOD__ ),
			$diff
		);

		$this->assertEmpty(
			$instance->getTableChangeOps( 'smw_di_number' )
		);
	}

	public function testGetHash() {

		$diff = [];

		$instance = new ChangeOp(
			DIWikiPage::newFromText( __METHOD__ ),
			$diff
		);

		$this->assertInternalType(
			'string',
			$instance->getHash()
		);
	}

	public function diffDataProvider() {

		$provider[] = [
			[],
			[ 'foo', [] ],
			[],
			[],
		];

		// Insert
		$provider[] = [
			[
				0 => [
				'insert' => [
				'smw_di_number' =>
				  [ 0 => [
					  's_id' => 3668,
					  'p_id' => 61,
					  'o_serialized' => '123',
					  'o_sortkey' => '123',
					],
				  ],
				'smw_fpt_mdat' =>
				  [ 0 => [
					  's_id' => 3668,
					  'o_serialized' => '1/2015/8/16/9/28/39',
					  'o_sortkey' => '2457250.8948958',
					],
				  ],
				],
				'delete' => [
				'smw_di_number' =>
				  [],
				'smw_fpt_mdat' =>
				  [],
				],
			  ],
			],
			[
			  'smw_fpt_mdat',
			  [
				'key' => '_MDAT',
				'p_id' => 29,
			  ],
			],
			[
				'smw_di_number' => [
				'insert' =>
				[ 0 => [
					's_id' => 3668,
					'p_id' => 61,
					'o_serialized' => '123',
					'o_sortkey' => '123',
				  ],
				],
			  ],
			  'smw_fpt_mdat' => [
				'property' =>
				[
				  'key' => '_MDAT',
				  'p_id' => 29,
				],
				'insert' => [
				  0 => [
					's_id' => 3668,
					'o_serialized' => '1/2015/8/16/9/28/39',
					'o_sortkey' => '2457250.8948958',
				  ],
				],
			  ],
			],
			[
			  0 => 61,
			  1 => 3668,
			  2 => 29,
			]
		];

		// Insert / Delete
		$provider[] = [
			[
			  0 =>
			  [
				'insert' =>
				[
				  'smw_di_number' =>
				  [
					0 =>
					[
					  's_id' => 3668,
					  'p_id' => 61,
					  'o_serialized' => '1001',
					  'o_sortkey' => '1001',
					],
				  ],
				  'smw_fpt_mdat' =>
				  [
					0 =>
					[
					  's_id' => 3668,
					  'o_serialized' => '1/2015/8/16/9/56/53',
					  'o_sortkey' => '2457250.9145023',
					],
				  ],
				],
				'delete' =>
				[
				  'smw_di_number' =>
				  [
					0 =>
					[
					  's_id' => 3668,
					  'p_id' => 61,
					  'o_serialized' => '123',
					  'o_sortkey' => '123',
					],
				  ],
				  'smw_fpt_mdat' =>
				  [
					0 =>
					[
					  's_id' => 3668,
					  'o_serialized' => '1/2015/8/16/9/28/39',
					  'o_sortkey' => '2457250.8948958',
					],
				  ],
				],
			  ],
			],
			[
			  'smw_fpt_mdat',
			  [
				'key' => '_MDAT',
				'p_id' => 29,
			  ],
			],
			[
			  'smw_di_number' =>
			  [
				'insert' =>
				[
				  0 =>
				  [
					's_id' => 3668,
					'p_id' => 61,
					'o_serialized' => '1001',
					'o_sortkey' => '1001',
				  ],
				],
				'delete' =>
				[
				  0 =>
				  [
					's_id' => 3668,
					'p_id' => 61,
					'o_serialized' => '123',
					'o_sortkey' => '123',
				  ],
				],
			  ],
			  'smw_fpt_mdat' =>
			  [
				'property' =>
				[
				  'key' => '_MDAT',
				  'p_id' => 29,
				],
				'insert' =>
				[
				  0 =>
				  [
					's_id' => 3668,
					'o_serialized' => '1/2015/8/16/9/56/53',
					'o_sortkey' => '2457250.9145023',
				  ],
				],
				'delete' =>
				[
				  0 =>
				  [
					's_id' => 3668,
					'o_serialized' => '1/2015/8/16/9/28/39',
					'o_sortkey' => '2457250.8948958',
				  ],
				],
			  ],
			],
			[
			  0 => 61,
			  1 => 3668,
			  2 => 29,
			]
		];

		return $provider;
	}


}
