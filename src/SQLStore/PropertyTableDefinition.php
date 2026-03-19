<?php

namespace SMW\SQLStore;

use OutOfBoundsException;

/**
 * Simple data container for storing information about property tables. A
 * property table is a DB table that is used to store subject-property-value
 * records about data in SMW. Tables mostly differ in the composition of the
 * value, but also in whether the property is explicitly named (or fixed),
 * and in the way subject pages are referred to.
 *
 *
 * @license GPL-2.0-or-later
 * @since 1.8
 *
 * @author Nischay Nahata
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyTableDefinition {

	/**
	 * A table that is part of Semantic MediaWiki core.
	 */
	const TYPE_CORE = 'type/core';

	/**
	 * A custom table added for example by an extension.
	 */
	const TYPE_CUSTOM = 'type/custom';

	/**
	 * Boolean that states how subjects are stored. If true, a column "s_id"
	 * with an SMW page id is used. If false, two columns "s_title" and
	 * "s_namespace" are used. The latter de-normalized form cannot store
	 * sortkeys and interwiki prefixes, and is used only for the redirect
	 * table. New tables should really keep the default "true" here.
	 *
	 * @since 1.8
	 * @var bool
	 */
	protected $idSubject = true;

	/**
	 * @var string
	 */
	private $tableType = '';

	/**
	 * Factory method to create an instance for a given
	 * DI type and the given table name.
	 *
	 * @since 1.8
	 */
	public function __construct(
		protected $diType,
		protected $name,
		protected $fixedProperty = false,
	) {
	}

	/**
	 * Method to return the fields for this table
	 *
	 * @since 1.8
	 *
	 * @param SQLStore $store
	 *
	 * @return array
	 */
	public function getFields( SQLStore $store ) {
		$diHandler = $store->getDataItemHandlerForDIType( $this->diType );
		return $diHandler->getTableFields();
	}

	/**
	 * @see $idSubject
	 *
	 * @since 1.8
	 *
	 * @return bool
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
	 * @since 3.2
	 *
	 * @param string $tableType
	 *
	 * @return bool
	 */
	public function isTableType( string $tableType ): bool {
		return $this->tableType === $tableType;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $tableType
	 */
	public function setTableType( string $tableType ) {
		$this->tableType = $tableType;
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
	 * @throws OutOfBoundsException
	 */
	public function getFixedProperty() {
		if ( $this->fixedProperty === false ) {
			throw new OutOfBoundsException( 'Attempt to get the fixed property from a table that does not hold one' );
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
	 * @return bool
	 */
	public function isFixedPropertyTable(): bool {
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
	 * @return int
	 */
	public function getDiType() {
		return $this->diType;
	}

}
