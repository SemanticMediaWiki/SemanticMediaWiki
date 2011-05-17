<?php
/**
 * @file
 * @ingroup SMWDataItems
 */

/**
 * This class implements number data items.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDINumber extends SMWDataItem {

	/**
	 * Internal value.
	 * @var numeric
	 */
	protected $m_number;

	public function __construct( $number ) {
		if ( !is_numeric( $number ) ) {
			throw new SMWDataItemException( "Initialisation value '$number' is not a number." );
		}
		$this->m_number = $number;
	}

	public function getDIType() {
		return SMWDataItem::TYPE_NUMBER;
	}

	public function getNumber() {
		return $this->m_number;
	}

	public function getSortKey() {
		return $this->m_number;
	}

	/**
	 * @see SMWDataItem::getSortKeyDataItem()
	 * @return SMWDataItem
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
	 * @return SMWDINumber
	 */
	public static function doUnserialize( $serialization ) {
		return new SMWDINumber( floatval( $serialization ) );
	}

}
