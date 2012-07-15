<?php
/**
 * File holding interface SMWDataItemHandler and factory class SMWDIHandlerFactory, the base for all dataitem handlers in SMW.
 *
 * @author Nischay Nahata
 *
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * Classes implementing this represent all store layout that is known about a certain dataitem
 *
 * @since SMW.storerewrite
 *
 * @ingroup SMWDataItemsHandlers
 */
interface SMWDataItemHandler {

	/**
	 * Method to return array of fields and indexes for a DI type
	 * @since SMW.storerewrite
	 *
	 * Tables declare value columns ("object fields") by specifying their name
	 * and type. Types are given using letters:
	 * - t for strings of the same maximal length as MediaWiki title names,
	 * - l for arbitrarily long strings; searching/sorting with such data may
	 * be limited for performance reasons,
	 * - w for strings as used in MediaWiki for encoding interwiki prefixes
	 * - n for namespace numbers (or other similar integers)
	 * - f for floating point numbers of double precision
	 * - p for a reference to an SMW ID as stored in the smw_ids table; this
	 *   corresponds to a data entry of ID "tnwt".
	 *
	 * @return array
	 */
	public function getTableFields();

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * @since SMW.storerewrite
	 *
	 * @param SMWDataItem
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem );

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * This array is used to perform all insert operations into the DB
	 * To optimize return minimum fields having indexes
	 * @since SMW.storerewrite
	 *
	 * @param SMWDataItem
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem );

	/**
	 * Method to return the field used to select this type of DataItem
	 * Careful when modifying these; the rest of the code assumes the following
	 * 1 - return column name if present in the same table (hence no join needed with smw_ids)
	 * 2 - return column name of starting with 'smw' if the column is present in smw_ids (see WikiPage)
	 * 3 - return '' if no such column exists
	 *
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getIndexField();

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * Careful when modifying these; the rest of the code assumes the following
	 * 1 - return column name if present in the same table (hence no join needed with smw_ids)
	 * 2 - return column name of starting with 'smw' if the column is present in smw_ids (see WikiPage)
	 * 3 - return '' if no such column exists
	 *
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getLabelField();

	/**
	 * Method to create a dataitem from a type ID and array of DB keys.
	 *
	 * @since SMW.storerewrite
	 * @param $typeId typeId of the DataItem
	 * @param $dbkeys array of mixed
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $typeId, $dbkeys );

}

/**
 * Factory Class for creating objects of DIHandlers
 *
 * @since SMW.storerewrite
 *
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerFactory {

	/**
	 * Gets an object of the dataitem handler from the dataitem provided.
	 *
	 * @since SMW.storerewrite
	 *
	 * @param $dataItemID constant
	 *
	 * @throws MWException
	 * @return SMWDataItemHandler
	 */
	public static function getDataItemHandlerForDIType( $diType ) {
		switch ( $diType ) {
			case SMWDataItem::TYPE_NUMBER:    return new SMWDIHandlerNumber;
			case SMWDataItem::TYPE_STRING:    return new SMWDIHandlerString;
			case SMWDataItem::TYPE_BLOB:      return new SMWDIHandlerBlob;
			case SMWDataItem::TYPE_BOOLEAN:   return new SMWDIHandlerBoolean;
			case SMWDataItem::TYPE_URI:       return new SMWDIHandlerUri;
			case SMWDataItem::TYPE_TIME:      return new SMWDIHandlerTime;
			case SMWDataItem::TYPE_GEO:       return new SMWDIHandlerGeoCoord;
			case SMWDataItem::TYPE_CONTAINER: return new SMWDIHandlerContainer;
			case SMWDataItem::TYPE_WIKIPAGE:  return new SMWDIHandlerWikiPage;
			case SMWDataItem::TYPE_CONCEPT:   return new SMWDIHandlerConcept;
			case SMWDataItem::TYPE_PROPERTY:  return new SMWDIHandlerProperty;
			case SMWDataItem::TYPE_ERROR:	case SMWDataItem::TYPE_NOTYPE: default:
				throw new MWException( "The value \"$diType\" is not a valid dataitem ID." );
		}
	}
}