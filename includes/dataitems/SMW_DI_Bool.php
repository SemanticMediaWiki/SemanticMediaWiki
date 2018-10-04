<?php
/**
 * @ingroup SMWDataItems
 */

use SMW\Exception\DataItemException;

/**
 * This class implements Boolean data items.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDIBoolean extends SMWDataItem {

	/**
	 * Internal value.
	 * @var bool
	 */
	protected $m_boolean;

	public function __construct( $boolean ) {
		if ( !is_bool( $boolean ) ) {
			throw new DataItemException( "Initialization value '$boolean' is not a boolean." );
		}

		$this->m_boolean = ( $boolean == true );
	}

	public function getDIType() {
		return SMWDataItem::TYPE_BOOLEAN;
	}

	public function getBoolean() {
		return $this->m_boolean;
	}

	public function getSerialization() {
		return $this->m_boolean ? 't' : 'f';
	}

	public function getSortKey() {
		return $this->m_boolean ? 1 : 0;
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @return SMWDIBoolean
	 */
	public static function doUnserialize( $serialization ) {
		if ( $serialization == 't' ) {
			return new SMWDIBoolean( true );
		} elseif  ( $serialization == 'f' ) {
			return new SMWDIBoolean( false );
		} else {
			throw new DataItemException( "Boolean data item unserialised from illegal value '$serialization'" );
		}
	}

	public function equals( SMWDataItem $di ) {
		if ( $di->getDIType() !== SMWDataItem::TYPE_BOOLEAN ) {
			return false;
		}
		return $di->getBoolean() === $this->m_boolean;
	}
}
