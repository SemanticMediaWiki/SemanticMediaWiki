<?php

/**
 * Simple data container for storing information about property tables. A
 * property table is a DB table that is used to store subject-property-value
 * records about data in SMW. Tables mostly differ in the composition of the
 * value, but also in whether the property is explicitly named (or fixed),
 * and in the way subject pages are referred to.
 *
 * @file SMW_SQLStore3Table.php
 * @ingroup SMWStore
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
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
	 * If the table is only for one property, this field holds its name.
	 * Empty otherwise. Tables without a fixed property have a column "p_id"
	 * for storing the SMW page id of the property.
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
	 * State if a table is reserved for "special properties" (properties that
	 * are pre-defined in SMW). This is mainly for optimization, since we do
	 * not want to join with the SMW page id table to find the property for an
	 * ID when it is likely that the ID is fixed and cached.
	 *
	 * @var unknown_type
	 */
	public $specpropsonly = false;

	/**
	* Factory method to create an instance for a given
	* DI type and the given table name.
	*
	* @since SMW.storerewrite
	*
	* @param $DIType constant
	* @param $tableName string
	* @param $fixedProperty
	* @return $table SMWSQLStore3Table
	*/
	public function __construct( $DIType, $tableName, $fixedProperty = false ) {
		$this->name = $tableName;
		$this->fixedproperty = $fixedProperty;
		$this->diType = $DIType;
	}
	/**
	* Method to return the fields for this table
	* 
	* @since SMW.storerewrite
	*
	* @return array
	*/
	public function getFields() {
		$diHandler = SMWDIHandlerFactory::getDataItemHandlerForDIType( $this->diType );
		return $diHandler->getTableFields();
	}
}
