<?php

namespace SMW\SQLStore;

use ArrayIterator;
use IteratorAggregate;
use SMW\SQLStore\ChangeOp\TableChangeOp;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class CompositePropertyTableDiffIterator implements IteratorAggregate {

	/**
	 * @var array
	 */
	private $diff = array();

	/**
	 * @var array
	 */
	private $fixedPropertyRecords = array();

	/**
	 * @since 2.3
	 *
	 * @param array $diff
	 */
	public function __construct( array $diff = array() ) {
		$this->diff = $diff;
	}

	/**
	 * @since 2.3
	 *
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator( $this->diff );
	}

	/**
	 * @since 2.3
	 *
	 * @param array $insertRecord
	 * @param array $deleteRecord
	 */
	public function addTableRowsToCompositeDiff( array $insertRecord, array $deleteRecord ) {
		$this->diff[] = array(
			'insert' => $insertRecord,
			'delete' => $deleteRecord
		);
	}

	/**
	 * @since 2.3
	 *
	 * @param array $fixedPropertyRecord
	 */
	public function addFixedPropertyRecord( $tableName, array $fixedPropertyRecord ) {
		$this->fixedPropertyRecords[$tableName] = $fixedPropertyRecord;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getFixedPropertyRecords() {
		return $this->fixedPropertyRecords;
	}

	/**
	 * ChangeOp (TableChangeOp/FieldChangeOp) representation of the composite
	 * diff.
	 *
	 * @since 2.4
	 *
	 * @param string|null $table
	 *
	 * @return TableChangeOp[]|[]
	 */
	public function getTableChangeOps( $table = null ) {

		$tableChangeOps = array();

		foreach ( $this->getOrderedDiffByTable( $table ) as $tableName => $diff ) {
			$tableChangeOps[] = new TableChangeOp( $tableName, $diff );
		}

		return $tableChangeOps;
	}

	/**
	 * Simplified (ordered by table) diff array to allow for an easier
	 * post-processing
	 *
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getOrderedDiffByTable( $table = null ) {

		$ordered = array();

		foreach ( $this as $diff ) {
			foreach ( $diff as $key => $value ) {
				foreach ( $value as $tableName => $val ) {

					if ( $val === array() || ( $table !== null && $table !== $tableName ) ) {
						continue;
					}

					if ( isset( $this->fixedPropertyRecords[$tableName] ) ) {
						$ordered[$tableName]['property'] = $this->fixedPropertyRecords[$tableName];
					}

					if ( !isset( $ordered[$tableName] ) ) {
						$ordered[$tableName] = array();
					}

					if ( !isset( $ordered[$tableName][$key] ) ) {
						$ordered[$tableName][$key] = array();
					}

					foreach ( $val as $v ) {
						$ordered[$tableName][$key][] = $v;
					}
				}
			}
		}

		return $ordered;
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getCombinedIdListOfChangedEntities() {

		$list = array();

		foreach ( $this->getOrderedDiffByTable() as $diff ) {

			if ( isset( $diff['insert'] )  ) {
				$this->addToIdList( $list, $diff['insert'] );
			}

			if ( isset( $diff['delete'] )  ) {
				$this->addToIdList( $list, $diff['delete'] );
			}

			if ( isset( $diff['property'] )  ) {
				$list[$diff['property']['p_id']] = null;
			}
		}

		return array_keys( $list );
	}

	private function addToIdList( &$list, $value ) {
		foreach ( $value as $element ) {

			if ( isset( $element['p_id'] ) ) {
				$list[$element['p_id']] = null;
			}

			if ( isset( $element['s_id'] ) ) {
				$list[$element['s_id']] = null;
			}

			if ( isset( $element['o_id'] ) ) {
				$list[$element['o_id']] = null;
			}
		}
	}

}
