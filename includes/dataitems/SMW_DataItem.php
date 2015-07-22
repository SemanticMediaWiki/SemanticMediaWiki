<?php

/**
 * This group contains all parts of SMW that relate to the processing of dataitems
 * of various types.
 *
 * @defgroup SMWDataItems SMWDataItems
 * @ingroup SMW
 */

/**
 * Objects of this type represent all that is known about a certain piece of
 * data that could act as the value of some property. Data items only represent
 * the stored data, and are thus at the core of SMW's data model. Data items
 * are always immutable, i.e. they must not be changed after creation (and this
 * is mostly enforced by the API with some minor exceptions).
 *
 * The set of available data items is fixed and cannot be extended. These are
 * the kinds of information that SMW can process. Their concrete use and
 * handling might depend on the context in which they are used. In particular,
 * property values may be influences by settings made for their property. This
 * aspect, however, is not part of the data item API.
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
	/**
	 * Data item ID for SMWDIString.
	 * @deprecated Will vanish after SMW 1.9; use TYPE_BLOB instead.
	 */
	const TYPE_STRING    = 2;
	/// Data item ID for SMWDIBlob
	const TYPE_BLOB      = 2;
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
	 * Convenience method that returns a constant that defines the concrete
	 * class that implements this data item. Used to switch when processing
	 * data items.
	 * @return integer that specifies the basic type of data item
	 */
	abstract public function getDIType();

	/**
	 * Return a value that can be used for sorting data of this type.
	 * If the data is of a numerical type, the sorting must be done in
	 * numerical order. If the data is a string, the data must be sorted
	 * alphabetically.
	 *
	 * @note Every data item returns a sort key, even if there is no
	 * natural linear order for the type. SMW must order listed data
	 * in some way in any case. If there is a natural order (e.g. for
	 * Booleans where false < true), then the sortkey must agree with
	 * this order (e.g. for Booleans where false maps to 0, and true
	 * maps to 1).
	 *
	 * @note Wiki pages are a special case in SMW. They are ordered by a
	 * sortkey that is assigned to them as a property value. When pages are
	 * sorted, this data should be used if possible.
	 *
	 * @return float|string
	 */
	abstract public function getSortKey();

	/**
	 * Method to compare two SMWDataItems
	 * This should result true only if they are of the same DI type
	 * and have the same internal value
	 *
	 * @since 1.8
	 *
	 * @param SMWDataItem $di
	 * @return boolean
	 */
	abstract public function equals( SMWDataItem $di );

	/**
	 * Create a data item that represents the sortkey, i.e. either an
	 * SMWDIBlob or an SMWDINumber. For efficiency, these subclasses
	 * overwrite this method to return themselves.
	 *
	 * @return SMWDataItem
	 */
	public function getSortKeyDataItem() {
		$sortKey = $this->getSortKey();

		if ( is_numeric( $sortKey ) ) {
			return new SMWDINumber( $sortKey );
		}

		return new SMWDIBlob( $sortKey );
	}

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
	 * @since 2.1
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getSerialization();
	}

	/**
	 * Create a data item of the given dataitem ID based on the the
	 * provided serialization string and (optional) typeid.
	 *
	 * @param $diType integer dataitem ID
	 * @param $serialization string
	 *
	 * @return SMWDataItem
	 */
	public static function newFromSerialization( $diType, $serialization ) {
		$diClass = self::getDataItemClassNameForId( $diType );
		return call_user_func( array( $diClass, 'doUnserialize' ), $serialization );
	}

	/**
	 * Gets the class name of the data item that has the provided type id.
	 *
	 * @param integer $diType Element of the SMWDataItem::TYPE_ enum
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return string
	 */
	public static function getDataItemClassNameForId( $diType ) {
		switch ( $diType ) {
			case self::TYPE_NUMBER:
				return 'SMWDINumber';
			case self::TYPE_BLOB:
				return 'SMWDIBlob';
			case self::TYPE_BOOLEAN:
				return 'SMWDIBoolean';
			case self::TYPE_URI:
				return 'SMWDIUri';
			case self::TYPE_TIME:
				return 'SMWDITime';
			case self::TYPE_GEO:
				return 'SMWDIGeoCoord';
			case self::TYPE_CONTAINER:
				return 'SMWDIContainer';
			case self::TYPE_WIKIPAGE:
				return 'SMWDIWikiPage';
			case self::TYPE_CONCEPT:
				return 'SMWDIConcept';
			case self::TYPE_PROPERTY:
				return 'SMWDIProperty';
			case self::TYPE_ERROR:
				return 'SMWDIError';
			case self::TYPE_NOTYPE: default:
				throw new InvalidArgumentException( "The value \"$diType\" is not a valid dataitem ID." );
		}
	}

}
