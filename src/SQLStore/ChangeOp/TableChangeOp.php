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
	 * @param array $filter
	 *
	 * @return FieldChangeOp[]|[]
	 */
	public function getFieldChangeOps( $opType = null, $filter = [] ) {

		if ( $opType !== null && !$this->hasChangeOp( $opType ) ) {
			return [];
		}

		$fieldOps = [];
		$changeOps = $this->changeOps;

		if ( $opType !== null ) {
			$changeOps = $this->changeOps[$opType];
		} elseif ( !isset( $this->changeOps[self::OP_DELETE] ) && !isset( $this->changeOps[self::OP_INSERT] ) )  {
			$changeOps = $this->changeOps;
		} else  {
			return array_merge(
				$this->getFieldChangeOps( self::OP_DELETE, $filter ),
				$this->getFieldChangeOps( self::OP_INSERT, $filter )
			);
		}

		unset( $changeOps['property'] );

		foreach ( $changeOps as $changeOp ) {

			// Filter defined as: [ 's_id' => [ 42 => true, 1001 => true ] ]
			if ( isset( $filter['s_id' ] ) && isset( $changeOp['s_id'] ) && isset( $filter['s_id'][$changeOp['s_id']] ) ) {
				continue;
			}

			if ( isset( $this->changeOps['property'] ) ) {
				$changeOp['p_id'] = $this->changeOps['property']['p_id'];
			}

			$fieldOps[] = new FieldChangeOp( $changeOp, $opType );
		}

		return $fieldOps;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function __toString() {
		return json_encode( $this->toArray() );
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function toArray() {
		return [ $this->tableName => $this->changeOps ];
	}

}
