<?php

/**
 * Simple data container for storing information about property tables. A
 * property table is a DB table that is used to store subject-property-value
 * records about data in SMW. Tables mostly differ in the composition of the
 * value, but also in whether the property is explicitly named (or fixed),
 * and in the way subject pages are referred to.
 *
 * @since 1.8
 *
 * @ingroup SMWStore
 *
 * @licence GNU GPL v2+
 * @author Nischay Nahata
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWSQLStore3Table {

	/**
	 * Name of the table in the DB.
	 *
	 * @since 1.8
	 * @var string
	 */
	protected $name;

	/**
	 * DIType of this table.
	 *
	 * @since 1.8
	 * @var integer
	 */
	protected $diType;

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
	 * @since 1.8
	 * @var string|boolean false
	 */
	protected $fixedProperty;

	/**
	 * Boolean that states how subjects are stored. If true, a column "s_id"
	 * with an SMW page id is used. If false, two columns "s_title" and
	 * "s_namespace" are used. The latter de-normalized form cannot store
	 * sortkeys and interwiki prefixes, and is used only for the redirect
	 * table. New tables should really keep the default "true" here.
	 *
	 * @since 1.8
	 * @var boolean
	 */
	protected $idSubject = true;

	/**
	* Factory method to create an instance for a given
	* DI type and the given table name.
	*
	* @since 1.8
	*
	* @param integer $DIType constant
	* @param string $tableName logocal table name (not the DB version)
	* @param string|false $fixedProperty property key if any
	*/
	public function __construct( $DIType, $tableName, $fixedProperty = false ) {
		$this->name = $tableName;
		$this->fixedProperty = $fixedProperty;
		$this->diType = $DIType;
	}

	/**
	* Method to return the fields for this table
	*
	* @since 1.8
	*
	* @param SMWSQLStore3 $store
	*
	* @return array
	*/
	public function getFields( SMWSQLStore3 $store ) {
		$diHandler = $store->getDataItemHandlerForDIType( $this->diType );
		return $diHandler->getTableFields();
	}

	/**
	 * @see $idSubject
	 *
	 * @since 1.8
	 *
	 * @return boolean
	 */
	public function usesIdSubject() {
		return $this->idSubject;
	}

	/**
	 * @see $idSubject
	 *
	 * @param $usesIdSubject
	 *
	 * @since 1.8
	 */
	public function setUsesIdSubject( $usesIdSubject ) {
		$this->idSubject = $usesIdSubject;
	}

	/**
	 * Returns the name of the fixed property which this table is for.
	 * Throws an exception when called on a table not for any fixed
	 * property, so call @see isFixedPropertyTable first when appropriate.
	 *
	 * @see $fixedProperty
	 *
	 * @since 1.8
	 *
	 * @return string
	 * @throws MWException
	 */
	public function getFixedProperty() {
		if ( $this->fixedProperty === false ) {
			throw new MWException( 'Attempt to get the fixed property from a table that does not hold one' );
		}

		return $this->fixedProperty;
	}

	/**
	 * Returns if the table holds a fixed property or is a general table.
	 *
	 * @see $fixedProperty
	 *
	 * @since 1.8
	 *
	 * @return boolean
	 */
	public function isFixedPropertyTable() {
		return $this->fixedProperty !== false;
	}

	/**
	 * Returns the name of the table in the database.
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns @see $diType
	 *
	 * @since 1.8
	 *
	 * @return integer
	 */
	public function getDiType() {
		return $this->diType;
	}

}
