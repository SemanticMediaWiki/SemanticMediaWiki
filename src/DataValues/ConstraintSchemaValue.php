<?php

namespace SMW\DataValues;

use SMW\Localizer;
use SMWWikiPageValue as WikiPageValue;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Property\SpecificationLookup;

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

		if ( $contextPage->getNamespace() === NS_CATEGORY && $schema['type'] !== 'CLASS_CONSTRAINT_SCHEMA' ) {
			$this->addErrorMsg( [ 'smw-datavalue-constraint-schema-category-wrong-type', $value, 'CLASS_CONSTRAINT_SCHEMA' ] );
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
