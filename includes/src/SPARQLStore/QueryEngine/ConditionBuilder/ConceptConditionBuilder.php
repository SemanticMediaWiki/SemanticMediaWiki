<?php

namespace SMW\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;

use SMW\Query\Language\Description;
use SMW\Query\Language\ConceptDescription;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\ApplicationFactory;

use SMWExporter as Exporter;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ConceptConditionBuilder implements ConditionBuilder {

	/**
	 * @var CompoundConditionBuilder
	 */
	private $compoundConditionBuilder;

	/**
	 * @var Exporter
	 */
	private $exporter;

	/**
	 * @since 2.1
	 *
	 * @param CompoundConditionBuilder|null $compoundConditionBuilder
	 */
	public function __construct( CompoundConditionBuilder $compoundConditionBuilder = null ) {
		$this->compoundConditionBuilder = $compoundConditionBuilder;
		$this->exporter = Exporter::getInstance();
	}

	/**
	 * @since 2.1
	 *
	 * @param Description $description
	 *
	 * @return boolean
	 */
	public function canBuildConditionFor( Description $description ) {
		return $description instanceOf ConceptDescription;
	}

	/**
	 * @since 2.1
	 *
	 * @param CompoundConditionBuilder $compoundConditionBuilder
	 *
	 * @return ConditionBuilder
	 */
	public function setCompoundConditionBuilder( CompoundConditionBuilder $compoundConditionBuilder ) {
		$this->compoundConditionBuilder = $compoundConditionBuilder;
		return $this;
	}

	/**
	 * Create a Condition from a ConceptDescription
	 *
	 * @param ConceptDescription $description
	 * @param string $joinVariable
	 * @param DIProperty|null $orderByProperty
	 *
	 * @return Condition
	 */
	public function buildCondition( Description $description, $joinVariable, $orderByProperty = null ) {

		$conceptDescription = $this->getConceptDescription( $description->getConcept() );

		if ( $conceptDescription === '' ) {
			return new FalseCondition();
		}

		return $this->compoundConditionBuilder->mapDescriptionToCondition(
			$conceptDescription,
			$joinVariable,
			$orderByProperty
		);
	}

	private function getConceptDescription( DIWikiPage $concept ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$value = $applicationFactory->getStore()->getSemanticData( $concept )->getPropertyValues( new DIProperty( '_CONC' ) );

		if ( $value === null || $value === array() ) {
			return '';
		}

		$value = end( $value );

		return $applicationFactory->newQueryParser()->getQueryDescription( $value->getConceptQuery() );
	}

}
