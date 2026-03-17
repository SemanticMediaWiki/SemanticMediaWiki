<?php

/**
 * @ingroup DataItems
 */

namespace SMW\DataItems;

use SMW\Exception\DataItemException;

/**
 * This class implements number data items.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup DataItems
 */
class Number extends DataItem {

	/**
	 * Internal value.
	 * @var float|int
	 */
	protected $m_number;

	public function __construct( $number ) {
		if ( !is_numeric( $number ) ) {
			throw new DataItemException( "Initialization value '$number' is not a number." );
		}
		$this->m_number = $number;
	}

	public function getDIType() {
		return DataItem::TYPE_NUMBER;
	}

	public function getNumber() {
		return $this->m_number;
	}

	public function getSortKey() {
		return $this->m_number;
	}

	/**
	 * @see DataItem::getSortKeyDataItem()
	 * @return DataItem
	 */
	public function getSortKeyDataItem() {
		return $this;
	}

	public function getSerialization() {
		return strval( $this->m_number );
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @note PHP can convert any string to some number, so we do not do
	 * validation here (because this would require less efficient parsing).
	 * @return Number
	 */
	public static function doUnserialize( $serialization ) {
		return new Number( floatval( $serialization ) );
	}

	public function equals( DataItem $di ) {
		if ( $di->getDIType() !== DataItem::TYPE_NUMBER ) {
			return false;
		}

		return $di->getNumber() === $this->m_number;
	}

}

// Deprecated since 7.0.0
class_alias( Number::class, 'SMWDINumber' );
