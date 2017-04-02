<?php

namespace SMW\SQLStore\ChangeOp;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class TableChangeOp {

	const OP_INSERT = 'insert';
	const OP_DELETE = 'delete';

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var array
	 */
	private $changeOps;

	/**
	 * @since 2.4
	 *
	 * @param string $tableName
	 * @param array $changeOps
	 */
	public function __construct( $tableName, array $changeOps ) {
		$this->tableName = $tableName;
		$this->changeOps = $changeOps;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

	/**
	 * @since 2.4
	 *
	 * @return boolean
	 */
	public function isFixedPropertyOp() {
		return isset( $this->changeOps['property'] );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 *
	 * @return null|string
	 */
	public function getFixedPropertyValueBy( $id ) {
		return $this->isFixedPropertyOp() && isset( $this->changeOps['property'][$id] ) ? $this->changeOps['property'][$id] : null;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $opType
	 *
	 * @return boolean
	 */
	public function hasChangeOp( $opType ) {
		return isset( $this->changeOps[$opType] );
	}

	/**
	 * @since 2.4
	 *
	 * @param string|null $opType
	 *
	 * @return FieldChangeOp[]|[]
	 */
	public function getFieldChangeOps( $opType = null ) {

		if ( $opType !== null && !$this->hasChangeOp( $opType ) ) {
			return array();
		}

		$fieldOps = array();
		$changeOps = $this->changeOps;

		if ( $opType !== null ) {
			$changeOps = $this->changeOps[$opType];
		}

		foreach ( $changeOps as $op ) {
			$fieldOps[] = new FieldChangeOp( $op );
		}

		return $fieldOps;
	}

}

