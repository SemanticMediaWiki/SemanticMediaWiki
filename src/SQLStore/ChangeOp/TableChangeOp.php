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
	 * @since 3.0
	 *
	 * @param string $field
	 *
	 * @return null|string
	 */
	public function getFixedPropertyValByField( $field ) {

		if ( $this->isFixedPropertyOp() && isset( $this->changeOps['property'][$field] ) ) {
			return $this->changeOps['property'][$field];
		}

		return null;
	}

	/**
	 * @deprecated since 3.0, use TableChangeOp::getFixedPropertyValByField
	 * @since 2.4
	 *
	 * @param string $field
	 *
	 * @return null|string
	 */
	public function getFixedPropertyValueBy( $field ) {
		return $this->getFixedPropertyValByField( $field );
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

		unset( $changeOps['property'] );

		foreach ( $changeOps as $type => $changeOp ) {

			if ( $opType !== null && $opType !== $type ) {
				continue;
			}

			if ( isset( $changeOp[0] ) && is_array( $changeOp[0] ) ) {
				$changeOp = $changeOp[0];
			}

			if ( isset( $this->changeOps['property'] ) ) {
				$changeOp['p_id'] = $this->changeOps['property']['p_id'];
			}

			$fieldOps[] = new FieldChangeOp( $changeOp, $type );
		}

		return $fieldOps;
	}

	/**
	 * @since 3.0
	 */
	public function __toString() {
		return json_encode( [ $this->tableName => $this->changeOps ], JSON_PRETTY_PRINT );
	}

}

