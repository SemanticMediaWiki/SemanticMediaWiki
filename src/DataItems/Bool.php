<?php
/**
 * @ingroup DataItems
 */

namespace SMW\DataItems;

use SMW\Exception\DataItemException;

/**
 * This class implements Boolean data items.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup DataItems
 */
class Boolean extends DataItem {

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
		return DataItem::TYPE_BOOLEAN;
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
	 * @return Boolean
	 */
	public static function doUnserialize( $serialization ) {
		if ( $serialization == 't' ) {
			return new Boolean( true );
		} elseif ( $serialization == 'f' ) {
			return new Boolean( false );
		} else {
			throw new DataItemException( "Boolean data item unserialised from illegal value '$serialization'" );
		}
	}

	public function equals( DataItem $di ) {
		if ( $di->getDIType() !== DataItem::TYPE_BOOLEAN ) {
			return false;
		}
		return $di->getBoolean() === $this->m_boolean;
	}
}

// Deprecated since 7.0.0
class_alias( Boolean::class, 'SMWDIBoolean' );
