<?php
/**
 * File holding abstract class SMWDataItemHandler, the base for all dataitem handlers in SMW.
 *
 * @author Nischay Nahata
 *
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
	 * @since 1.8
	 * @var SMWSQLStore3
	*/
	protected $store;

	/**
	 * @since 1.8
	 * @param SMWSQLStore3 $store
	 */
	public function __construct( SMWSQLStore3 $store ) {
		$this->store = $store;
	}

	/**
	 * Return array of fields for a DI type.
	 *
	 * Tables declare value columns ("object fields") by specifying their
	 * name and type. Types are given using letters:
	 * - t for strings of the same maximal length as MediaWiki title names,
	 * - l for arbitrarily long strings; searching/sorting with such data
	 *   may be limited for performance reasons,
	 * - w for strings as used in MediaWiki for encoding interwiki prefixes
	 * - n for namespace numbers (or other similar integers)
	 * - f for floating point numbers of double precision
	 * - p for a reference to an SMW ID as stored in the SMW IDs table;
	 *   this corresponds to a data entry of ID "tnwt".
	 *
	 * @since 1.8
	 * @return array
	 */
	abstract public function getTableFields();

	/**
	 * Return an array with all the field names and types that need to be
	 * retrieved from the database in order to create a dataitem using
	 * dataItemFromDBKeys(). The result format is the same as for
	 * getTableFields(), but usually with fewer field names.
	 *
	 * @note In the future, we will most likely use a method that return
	 * only a single field name. Currently, we still need an array for
	 * concepts.
	 *
	 * @since 1.8
	 * @return array
	 */
	abstract public function getFetchFields();

	/**
	 * Return an array of additional indexes that should be provided for
	 * the table using this DI handler. By default, SMWSQLStore3 will
	 * already create indexes for all standard select operations, based
	 * on the indexfield provided by getIndexField(). Hence, most handlers
	 * do not need to define any indexes.
	 *
	 * @since 1.8
	 * @return array
	 */
	public function getTableIndexes() {
		return array();
	}

	/**
	 * Return an array of fields=>values to conditions (WHERE part) in SQL
	 * queries for the given SMWDataItem. This method can return fewer
	 * fields than getInstertValues as long as they are enough to identify
	 * an item for search.
	 *
	 * @since 1.8
	 * @param SMWDataItem $dataItem
	 * @return array
	 */
	abstract public function getWhereConds( SMWDataItem $dataItem );

	/**
	 * Return an array of fields=>values that is to be inserted when
	 * writing the given SMWDataItem to the database. Values should be set
	 * for all columns, even if NULL. This array is used to perform all
	 * insert operations into the DB.
	 *
	 * @since 1.8
	 * @param SMWDataItem $dataItem
	 * @return array
	 */
	abstract public function getInsertValues( SMWDataItem $dataItem );

	/**
	 * Return the field used to select this type of SMWDataItem. In
	 * particular, this identifies the column that is used to sort values
	 * of this kind. Every type of data returns a non-empty string here.
	 *
	 * @since 1.8
	 * @return string
	 */
	abstract public function getIndexField();

	/**
	 * Return the label field for this type of SMWDataItem. This should be
	 * a string column in the database table that can be used for selecting
	 * values using criteria such as "starts with". The return value can be
	 * empty if this is not supported. This is preferred for SMWDataItem
	 * classes that do not have an obvious canonical string writing anyway.
	 *
	 * The return value can be a column name or the empty string (if the
	 * give type of SMWDataItem does not have a label field).
	 *
	 * @since 1.8
	 * @return string
	 */
	abstract public function getLabelField();

	/**
	 * Create a dataitem from an array of DB keys or a single DB key
	 * string. May throw an SMWDataItemException if the given DB keys
	 * cannot be converted back into a dataitem. Each implementation
	 * of this method must otherwise run without errors for both array
	 * and string inputs.
	 *
	 * @since 1.8
	 * @param array|string $dbkeys
	 * @throws SMWDataItemException
	 * @return SMWDataItem
	 */
	abstract public function dataItemFromDBKeys( $dbkeys );

}
