<?php
/**
 * @file
 * @ingroup SMWDataItems
 */

/**
 * This class implements Boolean data items.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDIBool extends SMWDataItem {

	/**
	 * Internal value.
	 * @var bool
	 */
	protected $m_boolean;

	public function __construct( $boolean, $typeid = '_boo' ) {
		parent::__construct( $typeid );
		$this->m_boolean = ( $boolean == true );
	}

	public function getDIType() {
		return SMWDataItem::TYPE_BOOL;
	}

	public function getBoolean() {
		return $this->m_boolean;
	}

	public function getSerialization() {
		return $this->m_boolean ? 't' : 'f';
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @return SMWDIBool
	 */
	public static function doUnserialize( $serialization, $typeid ) {
		if ( $serialization == 't' ) {
			return new SMWDIBool( true, $typeid );
		} elseif  ( $serialization == 'f' ) {
			return new SMWDIBool( true, $typeid );
		} else {
			throw new SMWDataItemException( "Boolean data item unserialised from illegal value '$serialization'" );
		}
	}

}
