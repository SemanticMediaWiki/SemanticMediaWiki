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
	 * Type of change operations
	 */
	const TYPE_INSERT = 'insert';
	const TYPE_DELETE = 'delete';

	/**
	 * @var array
	 */
	private $diff = array();

	/**
	 * @var array
	 */
	private $data = array();

	/**
	 * @var array
	 */
	private $orderedDiff = array();

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
		return md5( $this->hash . ( $this->subject !== null ? $this->subject->asBase()->getHash() : '' ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $data
	 */
	public function addDataRecord( $hash, array $data ) {
		$this->data[$hash] = $data;
	}

	/**
	 * @since 3.0
	 *
	 * @return TableChangeOp[]
	 */
	public function getDataChangeOps() {

		$dataChangeOps = array();

		foreach ( $this->data as $hash => $data ) {
			foreach ( $data as $tableName => $d ) {
				$dataChangeOps[] = new TableChangeOp( $tableName, $d );
			}
		}

		return $dataChangeOps;
	}

	/**
	 * @since 2.3
	 *
	 * @param array $insertChangeOp
	 * @param array $deleteChangeOp
	 */
	public function addTableDiffChangeOp( array $insertChangeOp, array $deleteChangeOp ) {

		$diff = array(
			'insert' => $insertChangeOp,
			'delete' => $deleteChangeOp
		);

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

		if ( $table === null && $this->orderedDiff !== array() ) {
			return $this->orderedDiff;
		}

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

		if ( $table === null ) {
			$this->orderedDiff = $ordered;
		}

		return $ordered;
	}

	/**
	 * @since 3.0
	 *
	 * @param string|null $type
	 *
	 * @return array
	 */
	public function getListOfChangedEntityIdsByType( $type = null ) {

		$changedEntities = array();

		foreach ( $this->getOrderedDiffByTable() as $diff ) {

			if ( ( $type === 'insert' || $type === null ) && isset( $diff['insert'] )  ) {
				$this->addToIdList( $changedEntities, $diff['insert'] );
			}

			if ( ( $type === 'delete' || $type === null ) && isset( $diff['delete'] )  ) {
				$this->addToIdList( $changedEntities, $diff['delete'] );
			}

			if ( $type === null && isset( $diff['property'] )  ) {
				$changedEntities[$diff['property']['p_id']] = true;
			}
		}

		return $changedEntities;
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getCombinedIdListOfChangedEntities() {
		return array_keys( $this->getListOfChangedEntityIdsByType() );
	}

	private function addToIdList( &$list, $value ) {
		foreach ( $value as $element ) {

			if ( isset( $element['p_id'] ) ) {
				$list[$element['p_id']] = true;
			}

			if ( isset( $element['s_id'] ) ) {
				$list[$element['s_id']] = true;
			}

			if ( isset( $element['o_id'] ) ) {
				$list[$element['o_id']] = true;
			}
		}
	}

}
