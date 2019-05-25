<?php

namespace SMW\DataValues;

use SMW\Localizer;
use SMWWikiPageValue as WikiPageValue;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Property\SpecificationLookup;
use SMW\Constraint\Constraint;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintSchemaValue extends WikiPageValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '__cschema';

	/**
	 * @var SpecificationLookup
	 */
	private $specificationLookup;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '', SpecificationLookup $specificationLookup ) {
		parent::__construct( self::TYPE_ID );
		$this->specificationLookup = $specificationLookup;
		$this->m_fixNamespace = SMW_NS_SCHEMA;
	}

	/**
	 * @see WikiPageValue::parseUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $value ) {
		parent::parseUserValue( $value );

		$dataItem = $this->getDataItem();
		$contextPage = $this->getContextPage();
		$schema = null;
		$error = [];

		if ( !$dataItem instanceof DIWikiPage || $contextPage === null ) {
			return;
		}

		$definitions = $this->specificationLookup->getSpecification(
			$dataItem,
			new DIProperty( '_SCHEMA_DEF' )
		);

		foreach ( $definitions as $definition ) {
			$schema = json_decode( $definition->getString(), true );
		}

		if ( $schema === null ) {
			return;
		}

		$ns = $contextPage->getNamespace();

		if ( $ns === NS_CATEGORY && $schema['type'] !== Constraint::CLASS_CONSTRAINT_SCHEMA ) {
			$error = [ 'smw-datavalue-constraint-schema-category-invalid-type', $value, Constraint::CLASS_CONSTRAINT_SCHEMA ];
		} elseif ( $ns === SMW_NS_PROPERTY && $schema['type'] !== Constraint::PROPERTY_CONSTRAINT_SCHEMA ) {
			$error = [ 'smw-datavalue-constraint-schema-property-invalid-type', $value, Constraint::PROPERTY_CONSTRAINT_SCHEMA ];
		}

		if ( $error !== [] ) {
			$this->addErrorMsg( $error );
		}
	}

	/**
	 * @see WikiPageValue::getWikiValue
	 *
	 * @return string
	 */
	public function getWikiValue() {
		return $this->getPrefixedText();
	}

}
