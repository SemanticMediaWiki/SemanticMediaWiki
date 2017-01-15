<?php

namespace SMW\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Description;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreter;
use SMWExporter as Exporter;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ConceptDescriptionInterpreter implements DescriptionInterpreter {

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
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function canInterpretDescription( Description $description ) {
		return $description instanceof ConceptDescription;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ) {

		$joinVariable = $this->compoundConditionBuilder->getJoinVariable();
		$orderByProperty = $this->compoundConditionBuilder->getOrderByProperty();

		$conceptDescription = $this->getConceptDescription(
			$description->getConcept()
		);

		if ( $conceptDescription === '' ) {
			return new FalseCondition();
		}

		$hash = 'concept-' . $conceptDescription->getQueryString();

		$this->compoundConditionBuilder->getCircularReferenceGuard()->mark( $hash );

		if ( $this->compoundConditionBuilder->getCircularReferenceGuard()->isCircularByRecursionFor( $hash ) ) {

			$this->compoundConditionBuilder->addError(
				array( 'smw-query-condition-circular', $description->getQueryString() )
			);

			return new FalseCondition();
		}

		$this->compoundConditionBuilder->setJoinVariable( $joinVariable );
		$this->compoundConditionBuilder->setOrderByProperty( $orderByProperty );

		$condition = $this->compoundConditionBuilder->mapDescriptionToCondition(
			$conceptDescription
		);

		$this->compoundConditionBuilder->getCircularReferenceGuard()->unmark( $hash );

		return $condition;
	}

	private function getConceptDescription( DIWikiPage $concept ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$value = $applicationFactory->getStore()->getSemanticData( $concept )->getPropertyValues(
			new DIProperty( '_CONC' )
		);

		if ( $value === null || $value === array() ) {
			return '';
		}

		$value = end( $value );

		$description = $applicationFactory->newQueryParser()->getQueryDescription(
			$value->getConceptQuery()
		);

		$this->findCircularDescription( $concept, $description );

		return $description;
	}

	private function findCircularDescription( $concept, $description ) {

		if ( $description instanceof ConceptDescription ) {
			if ( $description->getConcept()->equals( $concept ) ) {
				$this->compoundConditionBuilder->addError(
					array( 'smw-query-condition-circular', $description->getQueryString() )
				);
				return;
			}
		}

		if ( $description instanceof Conjunction || $description instanceof Disjunction ) {
			foreach ( $description->getDescriptions() as $desc ) {
				$this->findCircularDescription( $concept, $desc );
			}
		}
	}

}
