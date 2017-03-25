<?php

namespace SMW\SQLStore;

use ArrayIterator;
use IteratorAggregate;
use SMW\DIWikiPage;
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
	private $diff = [];

	/**
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * @var string
	 */
	private $hash = '';

	/**
	 * @var array
	 */
	private $fixedPropertyRecords = [];

	/**
	 * @since 2.3
	 *
	 * @param array $diff
	 */
	public function __construct( array $diff = [] ) {
		$this->diff = $diff;
	}

	/**
	 * @since 2.5
	 *
	 * @return DIWikiPage $subject
	 */
	public function setSubject( DIWikiPage $subject ) {
		$this->subject = $subject;
	}

	/**
	 * @since 2.5
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return $this->subject;
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
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getHash() {
		return md5( $this->hash . ( $this->subject !== null ? $this->subject->getHash() : '' ) );
	}

	/**
	 * @since 2.3
	 *
	 * @param array $insertRecord
	 * @param array $deleteRecord
	 */
	public function addTableRowsToCompositeDiff( array $insertRecord, array $deleteRecord ) {

		$diff = [
			'insert' => $insertRecord,
			'delete' => $deleteRecord
		];

		$this->diff[] = $diff;

		$this->hash .= json_encode( $diff );
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

		$tableChangeOps = [];

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

		$ordered = [];

		foreach ( $this as $diff ) {
			foreach ( $diff as $key => $value ) {
				foreach ( $value as $tableName => $val ) {

					if ( $val === [] || ( $table !== null && $table !== $tableName ) ) {
						continue;
					}

					if ( isset( $this->fixedPropertyRecords[$tableName] ) ) {
						$ordered[$tableName]['property'] = $this->fixedPropertyRecords[$tableName];
					}

					if ( !isset( $ordered[$tableName] ) ) {
						$ordered[$tableName] = [];
					}

					if ( !isset( $ordered[$tableName][$key] ) ) {
						$ordered[$tableName][$key] = [];
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

		$list = [];

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
