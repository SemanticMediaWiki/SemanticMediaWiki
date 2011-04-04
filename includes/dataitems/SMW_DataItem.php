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
 *
 * @ingroup SMWDataItems
 */
abstract class SMWDataItem {

	/// Data item ID for SMWDINumber
	const TYPE_NUMBER  = 1;
	/// Data item ID for SMWDIString
	const TYPE_STRING  = 2;
	///  Data item ID for SMWDIBlob
	const TYPE_BLOB    = 3;
	///  Data item ID for SMWDIBool
	const TYPE_BOOL    = 4;
	///  Data item ID for SMWDIURI
	const TYPE_URI     = 5;
	///  Data item ID for SMWDITimePoint
	const TYPE_TIME    = 6;
	///  Data item ID for SMWDIGeoCoords
	const TYPE_GEO     = 7;
	///  Data item ID for SMWDIContainer
	const TYPE_CONT    = 8;
	///  Data item ID for SMWDIWikiPage
	const TYPE_WIKIPAGE = 9;
	///  Data item ID for SMWDIConcept
	const TYPE_CONCEPT = 10;
	///  Data item ID for SMWDIProperty
	const TYPE_PROP    = 11;

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
	 * Get a UTF-8 encoded string serialization of this data item.
	 * The serialisation should be concise and need not be pretty, but it
	 * must allow unserialization. For this purpose
	 * @return string
	 */
	abstract public function getSerialization();

	/**
	 * Create a data item from the provided serialization string and type
	 * ID. This static method really needs to be re-implemented by each
	 * data item class. It is given here only for reference. Note that PHP
	 * does not support "abstract static".
	 * @return SMWDataItem
	 */
	public static function doUnserialize( $serialisation, $typeid ) {
		throw new ErrorException( "Called doUnserialize() on abstract base class SMWDataItem. This means that some data item implementation forgot to implement this method statically." );
	}

}
