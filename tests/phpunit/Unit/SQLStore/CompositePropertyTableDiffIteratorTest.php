<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\CompositePropertyTableDiffIterator;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\CompositePropertyTableDiffIterator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class CompositePropertyTableDiffIteratorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\CompositePropertyTableDiffIterator',
			new CompositePropertyTableDiffIterator()
		);
	}

	/**
	 * @dataProvider diffDataProvider
	 */
	public function testDiff( $list, $fixedPropertyRecord, $expectedOrdered, $expectedList ) {

		$instance = new CompositePropertyTableDiffIterator(
			$list
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
			$instance->getCombinedIdListOfChangedEntities()
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

		$instance = new CompositePropertyTableDiffIterator(
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

	public function testTryToGetTableChangeOpForSingleTable() {

		$diff = [];

		$instance = new CompositePropertyTableDiffIterator(
			$diff
		);

		$this->assertEmpty(
			$instance->getTableChangeOps( 'smw_di_number' )
		);
	}

	public function testGetHash() {

		$diff = [];

		$instance = new CompositePropertyTableDiffIterator(
			$diff
		);

		$instance->setSubject(
			DIWikiPage::newFromText( __METHOD__ )
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
