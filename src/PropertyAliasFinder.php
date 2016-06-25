<?php

namespace SMW;

namespace SMW;

/**
 * @license GNU GPL v2
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyAliasFinder {

	/**
	 * Array with entries "property alias" => "property id"
	 *
	 * @var string[]
	 */
	private $propertyAliases = array();

	/**
	 * @var string[]
	 */
	private $propertyAliasesByMsgKey = array();

	/**
	 * @var string[]
	 */
	private $canonicalPropertyAliases = array();

	/**
	 * @since 2.4
	 *
	 * @param array $propertyAliases
	 * @param array $canonicalPropertyAliases
	 */
	public function __construct( array $propertyAliases = array(), array $canonicalPropertyAliases = array() ) {
		$this->canonicalPropertyAliases = $canonicalPropertyAliases;

		foreach ( $propertyAliases as $alias => $id ) {
			$this->registerAliasByFixedLabel( $id, $alias );
		}
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getKnownPropertyAliases() {
		return $this->propertyAliases;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getKnownPropertyAliasesWithMsgKey() {
		return $this->propertyAliasesByMsgKey;
	}

	/**
	 * Add a new alias label to an existing property ID. Note that every ID
	 * should have a primary label.
	 *
	 * @param string $id string
	 * @param string $label
	 */
	public function registerAliasByFixedLabel( $id, $label ) {

		// Indicates an untranslated MW message key
		if ( $label !== '' && $label{0} === '<' ) {
			return null;
		}

		$this->propertyAliases[$label] = $id;
	}

	/**
	 * Register an alias using a message key to allow fetching localized
	 * labels dynamically.
	 *
	 * @since 2.4
	 *
	 * @param string $id
	 * @param string $msgKey
	 */
	public function registerAliasByMsgKey( $id, $msgKey ) {
		$this->propertyAliasesByMsgKey[$msgKey] = $id;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 *
	 * @return string|boolean
	 */
	public function findCanonicalPropertyAliasById( $id ) {
		return array_search( $id, $this->canonicalPropertyAliases );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 *
	 * @return string|boolean
	 */
	public function findPropertyAliasById( $id ) {
		return array_search( $id, $this->propertyAliases );
	}

	/**
	 * Find and return the ID for the pre-defined property of the given
	 * local label. If the label does not belong to a pre-defined property,
	 * return false.
	 *
	 * @param string $alias
	 *
	 * @return string|boolean
	 */
	public function findPropertyIdByAlias( $alias ) {

		if ( isset( $this->propertyAliases[$alias] ) ) {
			return $this->propertyAliases[$alias];
		} elseif ( isset( $this->canonicalPropertyAliases[$alias] ) ) {
			return $this->canonicalPropertyAliases[$alias];
		}

		return false;
	}

}
