<?php

namespace SMW\PropertyAnnotators;

use SMW\DIProperty;
use SMW\PropertyAnnotator;
use SMW\Rule\RuleDefinition;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleDefinitionPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @var RuleDefinition
	 */
	private $ruleDefinition;

	/**
	 * @var array
	 */
	private $predefinedPropertyList = array();

	/**
	 * @since 3.0
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param RuleDefinition $ruleDefinition
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, RuleDefinition $ruleDefinition = null ) {
		parent::__construct( $propertyAnnotator );
		$this->ruleDefinition = $ruleDefinition;
	}

	protected function addPropertyValues() {

		if ( $this->ruleDefinition === null ) {
			return;
		}

		$semanticData = $this->getSemanticData();

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_RL_TYPE' ),
			$this->dataItemFactory->newDIBlob( $this->ruleDefinition->get( RuleDefinition::RULE_TYPE ) )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_RL_DEF' ),
			$this->dataItemFactory->newDIBlob( $this->ruleDefinition )
		);

		if ( ( $desc = $this->ruleDefinition->get( 'description', '' ) ) !== '' ) {
			$semanticData->addPropertyObjectValue(
				new DIProperty( '_RL_DESC' ),
				$this->dataItemFactory->newDIBlob( $desc )
			);
		}

		foreach ( $this->ruleDefinition->get( RuleDefinition::RULE_TAG, [] ) as $tag ) {
			$semanticData->addPropertyObjectValue(
				new DIProperty( '_RL_TAG' ),
				$this->dataItemFactory->newDIBlob( mb_strtolower( $tag ) )
			);
		}
	}

}
