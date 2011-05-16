<?php
/**
 * File holding abstract class SMWDataItem, the base for all dataitems in SMW.
 *
 * @author Markus Krötzsch
 *
 * @file
 * @ingroup SMWDataItems
 */

/**
 * This group contains all parts of SMW that relate to the processing of dataitems
 * of various types.
 *
 * @defgroup SMWDataItems SMWDataItems
 * @ingroup SMW
 */

/**
 * Exception to be thrown when data items are created from unsuitable inputs.
 * 
 * @since 1.6
 */
class SMWDataItemException extends Exception {
}

/**
 * Objects of this type represent all that is known about a certain piece of
 * data that could act as the value of some property. Data items only represent
 * the stored data, and are thus at the core of SMW's data model. Data items
 * are always immutable, i.e. they must not be changed after creation (and this
 * is mostly enforced by the API with some minor exceptions).
 * 
 * The set of available data items is fixed and cannot be extended. These are
 * the kinds of information that SMW can process. However, a type ID can be held
 * by a data item, and this type might determine details of processing in some
 * contexts (for example, since it can be used to chose an implementation for
 * formatting this value for display in the wiki). Data items do not implement
 * such selection procedures -- they are nothing but data and provide only
 * minimal interfaces for accessing the stored data (or aspects of it).
 *
 * @since 1.6
 *
 * @ingroup SMWDataItems
 */
abstract class SMWDataItem {

	/// Data item ID that can be used to indicate that no data item class is appropriate
	const TYPE_NOTYPE    = 0;
	/// Data item ID for SMWDINumber
	const TYPE_NUMBER    = 1;
	/// Data item ID for SMWDIString
	const TYPE_STRING    = 2;
	///  Data item ID for SMWDIBlob
	const TYPE_BLOB      = 3;
	///  Data item ID for SMWDIBoolean
	const TYPE_BOOLEAN   = 4;
	///  Data item ID for SMWDIUri
	const TYPE_URI       = 5;
	///  Data item ID for SMWDITimePoint
	const TYPE_TIME      = 6;
	///  Data item ID for SMWDIGeoCoord
	const TYPE_GEO       = 7;
	///  Data item ID for SMWDIContainer
	const TYPE_CONTAINER = 8;
	///  Data item ID for SMWDIWikiPage
	const TYPE_WIKIPAGE  = 9;
	///  Data item ID for SMWDIConcept
	const TYPE_CONCEPT   = 10;
	///  Data item ID for SMWDIProperty
	const TYPE_PROPERTY  = 11;
	///  Data item ID for SMWDIError
	const TYPE_ERROR     = 12;

	/**
	 * The SMW type ID that governs the handling of this data item.
	 * This data should not be considered part of the value. It is
	 * provided merely to assist suitable handling and will not be
	 * stored with the data.
	 * @var string
	 */
	protected $m_typeid;

	/**
	 * Constructor.
	 * @param $typeid string the SMW type ID that governs the handling of this data item.
	 */
	public function __construct( $typeid ) {
		$this->m_typeid = $typeid;
	}

	/**
	 * Convenience method that returns a constant that defines the concrete
	 * class that implements this data item. Used to switch when processing
	 * data items.
	 * @return integer that specifies the basic type of data item
	 */
	abstract public function getDIType();

	/**
	 * Get the SMW type ID that governs the handling of this data item.
	 * @return string $typeid the SMW type ID
	 */
	public function getTypeID() {
		return $this->m_typeid;
	}

	/**
	 * Return a value that can be used for sorting data of this type.
	 * If the data is of a numerical type, the sorting must be done in
	 * numerical order. If the data is a string, the data must be sorted
	 * alphabetically.
	 *
	 * @return float or string 
	 */
	abstract public function getSortKey();

	/**
	 * Get a UTF-8 encoded string serialization of this data item.
	 * The serialisation should be concise and need not be pretty, but it
	 * must allow unserialization. Each subclass of SMWDataItem implements
	 * a static method doUnserialize() for this purpose.
	 * @return string
	 */
	abstract public function getSerialization();

	/**
	 * Get a hash string for this data item. Might be overwritten in
	 * subclasses to obtain shorter or more efficient hashes. 
	 * 
	 * @return string
	 */
	public function getHash() {
		return $this->getSerialization();
	}

	/**
	 * Create a data item of the given dataitem ID based on the the
	 * provided serialization string and (optional) typeid.
	 *
	 * @param $diType integer dataitem ID
	 * @param $serialization string
	 * @param $typeid string SMW type ID (optional)
	 * @return SMWDataItem
	 */
	public static function newFromSerialization( $diType, $serialization, $typeid = '' ) {
		$diClass = self::getDataItemClassNameForId( $diType );
		if ( $typeid !== '' ) {
			return call_user_func( array( $diClass, 'doUnserialize' ), $serialization, $typeid );
		} else {
			return call_user_func( array( $diClass, 'doUnserialize' ), $serialization );
		}
	}

	public static function getDataItemClassNameForId( $diType ) {
		switch ( $diType ) {
			case self::TYPE_NUMBER:    return "SMWDINumber";
			case self::TYPE_STRING:    return "SMWDIString";
			case self::TYPE_BLOB:      return "SMWDIBlob";
			case self::TYPE_BOOLEAN:   return "SMWDIBoolean";
			case self::TYPE_URI:       return "SMWDIUri";
			case self::TYPE_TIME:      return "SMWDITimePoint";
			case self::TYPE_GEO:       return "SMWDIGeoCoord";
			case self::TYPE_CONTAINER: return "SMWDIContainer";
			case self::TYPE_WIKIPAGE:  return "SMWDIWikiPage";
			case self::TYPE_CONCEPT:   return "SMWDIConcept";
			case self::TYPE_PROPERTY:  return "SMWDIProperty";
			case self::TYPE_ERROR:     return "SMWDIError";
			case self::TYPE_NOTYPE: default:
				throw new InvalidArgumentException( "The value \"$diType\" is not a valid dataitem ID." );
		}
	}

}
