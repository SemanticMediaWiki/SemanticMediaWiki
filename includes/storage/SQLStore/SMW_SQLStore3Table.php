<?php
/**
 * @file
 * @ingroup SMWStore
 * @since 1.8
 */

/**
 * Simple data container for storing information about property tables. A
 * property table is a DB table that is used to store subject-property-value
 * records about data in SMW. Tables mostly differ in the composition of the
 * value, but also in whether the property is explicitly named (or fixed),
 * and in the way subject pages are referred to.
 *
 * @ingroup SMWStore
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @since 1.8
 */
class SMWSQLStore3Table {

	/**
	 * Name of the table in the DB.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * DIType of this table.
	 *
	 * @var constant
	 */
	public $diType;

	/**
	 * If the table is only for one property, this field holds its key.
	 * Empty otherwise. Tables without a fixed property have a column "p_id"
	 * for storing the SMW page id of the property.
	 *
	 * @note It is important that this is the DB key form or special
	 * property key, not the label. This is not checked eagerly in SMW but
	 * can lead to spurious errors when properties are compared to each
	 * other or to the contents of the store.
	 *
	 * @var mixed String or false
	 */
	public $fixedproperty;

	/**
	 * Boolean that states how subjects are stored. If true, a column "s_id"
	 * with an SMW page id is used. If false, two columns "s_title" and
	 * "s_namespace" are used. The latter de-normalized form cannot store
	 * sortkeys and interwiki prefixes, and is used only for the redirect
	 * table. New tables should really keep the default "true" here.
	 *
	 * @var boolean
	 */
	public $idsubject = true;

	/**
	* Factory method to create an instance for a given
	* DI type and the given table name.
	*
	* @since 1.8
	*
	* @param insteger $DIType constant
	* @param string $tableName logocal table name (not the DB version)
	* @param string|false $fixedProperty property key if any
	* @return SMWSQLStore3Table $table
	*/
	public function __construct( $DIType, $tableName, $fixedProperty = false ) {
		$this->name = $tableName;
		$this->fixedproperty = $fixedProperty;
		$this->diType = $DIType;
	}

	/**
	* Method to return the fields for this table
	*
	* @since 1.8
	*
	* @return array
	*/
	public function getFields( SMWSQLStore3 $store ) {
		$diHandler = $store->getDataItemHandlerForDIType( $this->diType );
		return $diHandler->getTableFields();
	}
}
