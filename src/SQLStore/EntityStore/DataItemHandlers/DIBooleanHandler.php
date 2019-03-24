<?php

namespace SMW\SQLStore\EntityStore\DataItemHandlers;

use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\TableBuilder\FieldType;
use SMWDataItem as DataItem;
use SMWDIBoolean as DIBoolean;

/**
 * This class implements Store access to Boolean data items.
 *
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Nischay Nahata
 */
class DIBooleanHandler extends DataItemHandler {

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields() {
		return [
			'o_value' => FieldType::TYPE_BOOL
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields() {
		return [
			'o_value' => FieldType::TYPE_BOOL
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getWhereConds( DataItem $dataItem ) {
		return [
			'o_value' => $dataItem->getBoolean() ? 1 : 0,
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {
		return [
			'o_value' => $dataItem->getBoolean() ? 1 : 0,
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getIndexField() {
		return 'o_value';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getLabelField() {
		return 'o_value';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		global $wgDBtype;

		//PgSQL returns as t and f and need special handling http://archives.postgresql.org/pgsql-php/2010-02/msg00005.php
		if ( $wgDBtype == 'postgres' ) {
			$value = ( $dbkeys == 't' );
		} else {
			$value = ( $dbkeys == '1' );
		}

		return new DIBoolean( $value );
	}
}
