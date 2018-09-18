<?php

namespace SMW\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
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
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @var Exporter
	 */
	private $exporter;

	/**
	 * @since 2.1
	 *
	 * @param ConditionBuilder|null $conditionBuilder
	 */
	public function __construct( ConditionBuilder $conditionBuilder = null ) {
		$this->conditionBuilder = $conditionBuilder;
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

		$joinVariable = $this->conditionBuilder->getJoinVariable();
		$orderByProperty = $this->conditionBuilder->getOrderByProperty();

		$conceptDescription = $this->getConceptDescription(
			$description->getConcept()
		);

		if ( $conceptDescription === '' ) {
			return new FalseCondition();
		}

		$hash = 'concept-' . $conceptDescription->getQueryString();

		$this->conditionBuilder->getCircularReferenceGuard()->mark( $hash );

		if ( $this->conditionBuilder->getCircularReferenceGuard()->isCircular( $hash ) ) {

			$this->conditionBuilder->addError(
				[ 'smw-query-condition-circular', $description->getQueryString() ]
			);

			return new FalseCondition();
		}

		$this->conditionBuilder->setJoinVariable( $joinVariable );
		$this->conditionBuilder->setOrderByProperty( $orderByProperty );

		$condition = $this->conditionBuilder->mapDescriptionToCondition(
			$conceptDescription
		);

		$this->conditionBuilder->getCircularReferenceGuard()->unmark( $hash );

		return $condition;
	}

	private function getConceptDescription( DIWikiPage $concept ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$value = $applicationFactory->getStore()->getSemanticData( $concept )->getPropertyValues(
			new DIProperty( '_CONC' )
		);

		if ( $value === null || $value === [] ) {
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
				$this->conditionBuilder->addError(
					[ 'smw-query-condition-circular', $description->getQueryString() ]
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
