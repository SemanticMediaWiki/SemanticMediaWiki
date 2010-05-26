<?php

/**
 * File holding the SMWSQLStore2Table class
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * 
 * @file
 * @ingroup SMWStore
 */

/**
 * Simple data container for storing information about property tables. A
 * property table is a DB table that is used to store subject-property-value
 * records about data in SMW. Tables mostly differ in the composition of the
 * value, but also in whether the property is explicitly named (or fixed),
 * and in the way subject pages are referred to.
 * @ingroup SMWStore
 */
class SMWSQLStore2Table {
	/**
	 * Name of the table in the DB.
	 * 
	 * @var string
	 */
	public $name;
	
	/**
	 * Array with entries "fieldname => typeid" where the types are as given 
	 * for SMWSQLStore::getPropertyTables().
	 * 
	 * @var array
	 */
	public $objectfields;
	
	/**
	 * If the table is only for one property, this field holds its name.
	 * Empty otherwise. Tables without a fixed property have a column "p_id"
	 * for storing the SMW page id of the property.
	 * 
	 * @var mixed String or false
	 */
	public $fixedproperty;
	
	/**
	 * Strings of the form "field1,...,fieldN" for extra indexes that are to
	 * be built for this table. All tables have indexes on subject column(s)
	 * and property column (if any). Items can also be an array with the column
	 * name as first element, and a index type as second elemet to allow for
	 * custom index types.
	 * 
	 * @var array of string
	 */
	public $indexes;
	
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
	 * Constructor.
	 * 
	 * @param string $name
	 * @param array $objectFields
	 * @param array $indexes
	 * @param mixed $fixedProperty string or false
	 */
	public function __construct( $name, array $objectFields, array $indexes = array(), $fixedProperty = false ) {
		$this->name = $name;
		$this->objectfields = $objectFields;
		$this->fixedproperty = $fixedProperty;
		$this->indexes = $indexes;
	}

	/**
	 * @return string
	 */
	public function getFieldSignature() {
		// TODO: this was implode( $this->objectfields, '' ), which might indicate some error where this method is called.
		return implode( '', $this->objectfields );
	}
}