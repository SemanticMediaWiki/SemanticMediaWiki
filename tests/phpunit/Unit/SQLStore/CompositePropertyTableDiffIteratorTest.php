<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\CompositePropertyTableDiffIterator;

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

		$diff = array(
			0 => array(
				'insert' => array(
					'smw_di_number' => array(
						0 => array(
							's_id' => 3668,
							'p_id' => 61,
							'o_serialized' => '123',
							'o_sortkey' => '123',
						),
						1 => array(
							's_id' => 3668,
							'p_id' => 62,
							'o_serialized' => '1234',
							'o_sortkey' => '1234',
						),
					),
					'smw_fpt_mdat' => array(
						0 => array(
							's_id' => 3668,
							'o_serialized' => '1/2015/8/16/9/28/39',
							'o_sortkey' => '2457250.8948958',
						),
					),
				),
				'delete' => array(
					'smw_di_number' => array(),
					'smw_fpt_mdat'  => array(),
				)
			)
		);

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

		$diff = array();

		$instance = new CompositePropertyTableDiffIterator(
			$diff
		);

		$this->assertEmpty(
			$instance->getTableChangeOps( 'smw_di_number' )
		);
	}

	public function diffDataProvider() {

		$provider[] = array(
			array(),
			array( 'foo', array() ),
			array(),
			array(),
		);

		// Insert
		$provider[] = array(
			array(
				0 => array(
				'insert' => array(
				'smw_di_number' =>
				  array( 0 => array(
					  's_id' => 3668,
					  'p_id' => 61,
					  'o_serialized' => '123',
					  'o_sortkey' => '123',
					),
				  ),
				'smw_fpt_mdat' =>
				  array( 0 => array(
					  's_id' => 3668,
					  'o_serialized' => '1/2015/8/16/9/28/39',
					  'o_sortkey' => '2457250.8948958',
					),
				  ),
				),
				'delete' => array(
				'smw_di_number' =>
				  array(),
				'smw_fpt_mdat' =>
				  array(),
				),
			  ),
			),
			array(
			  'smw_fpt_mdat',
			  array(
				'key' => '_MDAT',
				'p_id' => 29,
			  ),
			),
			array(
				'smw_di_number' => array(
				'insert' =>
				array( 0 => array(
					's_id' => 3668,
					'p_id' => 61,
					'o_serialized' => '123',
					'o_sortkey' => '123',
				  ),
				),
			  ),
			  'smw_fpt_mdat' => array(
				'property' =>
				array(
				  'key' => '_MDAT',
				  'p_id' => 29,
				),
				'insert' => array(
				  0 => array(
					's_id' => 3668,
					'o_serialized' => '1/2015/8/16/9/28/39',
					'o_sortkey' => '2457250.8948958',
				  ),
				),
			  ),
			),
			array(
			  0 => 61,
			  1 => 3668,
			  2 => 29,
			)
		);

		// Insert / Delete
		$provider[] = array(
			array(
			  0 =>
			  array(
				'insert' =>
				array(
				  'smw_di_number' =>
				  array(
					0 =>
					array(
					  's_id' => 3668,
					  'p_id' => 61,
					  'o_serialized' => '1001',
					  'o_sortkey' => '1001',
					),
				  ),
				  'smw_fpt_mdat' =>
				  array(
					0 =>
					array(
					  's_id' => 3668,
					  'o_serialized' => '1/2015/8/16/9/56/53',
					  'o_sortkey' => '2457250.9145023',
					),
				  ),
				),
				'delete' =>
				array(
				  'smw_di_number' =>
				  array(
					0 =>
					array(
					  's_id' => 3668,
					  'p_id' => 61,
					  'o_serialized' => '123',
					  'o_sortkey' => '123',
					),
				  ),
				  'smw_fpt_mdat' =>
				  array(
					0 =>
					array(
					  's_id' => 3668,
					  'o_serialized' => '1/2015/8/16/9/28/39',
					  'o_sortkey' => '2457250.8948958',
					),
				  ),
				),
			  ),
			),
			array(
			  'smw_fpt_mdat',
			  array(
				'key' => '_MDAT',
				'p_id' => 29,
			  ),
			),
			array(
			  'smw_di_number' =>
			  array(
				'insert' =>
				array(
				  0 =>
				  array(
					's_id' => 3668,
					'p_id' => 61,
					'o_serialized' => '1001',
					'o_sortkey' => '1001',
				  ),
				),
				'delete' =>
				array(
				  0 =>
				  array(
					's_id' => 3668,
					'p_id' => 61,
					'o_serialized' => '123',
					'o_sortkey' => '123',
				  ),
				),
			  ),
			  'smw_fpt_mdat' =>
			  array(
				'property' =>
				array(
				  'key' => '_MDAT',
				  'p_id' => 29,
				),
				'insert' =>
				array(
				  0 =>
				  array(
					's_id' => 3668,
					'o_serialized' => '1/2015/8/16/9/56/53',
					'o_sortkey' => '2457250.9145023',
				  ),
				),
				'delete' =>
				array(
				  0 =>
				  array(
					's_id' => 3668,
					'o_serialized' => '1/2015/8/16/9/28/39',
					'o_sortkey' => '2457250.8948958',
				  ),
				),
			  ),
			),
			array(
			  0 => 61,
			  1 => 3668,
			  2 => 29,
			)
		);

		return $provider;
	}


}
