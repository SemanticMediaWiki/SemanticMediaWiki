<?php
/**
 * File holding abstract class SMWDataItemHandler, the base for all dataitem handlers in SMW.
 *
 * @author Nischay Nahata
 *
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * Classes extending this represent all store layout that is known about a certain dataitem
 *
 * @since 1.8
 *
 * @ingroup SMWDataItemsHandlers
 */
abstract class SMWDataItemHandler {

	/**
	* The store object.
	*/
	protected $store;

	public function __construct( SMWSQLStore $store ){
		$this->store = $store;
	}
	/**
	 * Method to return array of fields for a DI type
	 * @since 1.8
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
	abstract public function getTableFields();

	/**
	 * Method to return array of indexes for a DI type
	 * @since 1.8
	 *
	 * @return array
	 */
	abstract public function getTableIndexes();

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * @since 1.8
	 *
	 * @param SMWDataItem
	 * @return array
	 */
	abstract public function getWhereConds( SMWDataItem $dataItem );

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * This array is used to perform all insert operations into the DB
	 * NOTE - For Containers this only inserts the id of the container
	 * and rest data should be handled by recursion in calling code
	 *
	 * @since 1.8
	 *
	 * @param SMWDataItem
	 * @return array
	 */
	abstract public function getInsertValues( SMWDataItem $dataItem );

	/**
	 * Method to return the field used to select this type of DataItem
	 * Careful when modifying these; the rest of the code assumes the following
	 * 1 - return column name if present in the same table (hence no join needed with smw_ids)
	 * 2 - return column name of starting with 'smw' if the column is present in smw_ids (see WikiPage)
	 * 3 - return '' if no such column exists
	 *
	 * @since 1.8
	 * @return string
	 */
	abstract public function getIndexField();

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * Careful when modifying these; the rest of the code assumes the following
	 * 1 - return column name if present in the same table (hence no join needed with smw_ids)
	 * 2 - return column name of starting with 'smw' if the column is present in smw_ids (see WikiPage)
	 * 3 - return '' if no such column exists
	 *
	 * @since 1.8
	 * @return string
	 */
	abstract public function getLabelField();

	/**
	 * Method to create a dataitem from an array of DB keys.
	 *
	 * @since 1.8
	 * @param $dbkeys array of mixed
	 *
	 * @return SMWDataItem
	 */
	abstract public function dataItemFromDBKeys( $dbkeys );

}