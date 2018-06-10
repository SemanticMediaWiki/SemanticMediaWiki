<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\Elastic\QueryEngine\QueryBuilder;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConceptDescriptionInterpreter {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @var QueryParser
	 */
	private $queryParser;

	/**
	 * @since 3.0
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
		$this->queryParser = ApplicationFactory::getInstance()->newQueryParser();
	}

	/**
	 * @since 3.0
	 *
	 * @param ConceptDescription $description
	 *
	 * @return Condition|[]
	 */
	public function interpretDescription( ConceptDescription $description, $isConjunction = false ) {

		$concept = $description->getConcept();

		$value = $this->queryBuilder->getStore()->getPropertyValues(
			$concept,
			new DIProperty( '_CONC' )
		);

		if ( $value === null || $value === [] ) {
			return [];
		}

		$value = end( $value );

		$description = $this->queryParser->getQueryDescription(
			$value->getConceptQuery()
		);

		if ( $this->hasCircularConceptDescription( $description, $concept ) ) {
			return [];
		}

		$params = $this->queryBuilder->interpretDescription(
			$description,
			$isConjunction
		);

		$concept->setId(
			$this->queryBuilder->getID( $concept )
		);

		$termsLookup = $this->queryBuilder->getTermsLookup();

		$parameters = $termsLookup->newParameters(
			[
				'id' => $concept->getId(),
				'hash' => $concept->getHash(),
				'query.string' => $description->getQueryString(),
				'fingerprint' => $description->getFingerprint(),
				'params' => $params->toArray(),
			]
		);

		// Using the terms lookup to prefetch IDs from the lookup index
		if ( $this->queryBuilder->getOption( 'concept.terms.lookup' ) ) {
			$params = $termsLookup->lookup( 'concept', $parameters );
		}

		if ( $parameters->has( 'query.info' ) ) {
			$this->queryBuilder->addQueryInfo( $parameters->get( 'query.info' ) );
		}

		$condition = $this->queryBuilder->newCondition( $params );
		$condition->type( '' );

		return $condition;
	}

	private function hasCircularConceptDescription( $description, $concept ) {

		if ( $description instanceof ConceptDescription ) {
			if ( $description->getConcept()->equals( $concept ) ) {
				$this->queryBuilder->addError( [ 'smw-query-condition-circular', $description->getQueryString() ] );
				return true;
			}
		}

		if ( $description instanceof Conjunction || $description instanceof Disjunction ) {
			foreach ( $description->getDescriptions() as $desc ) {
				if ( $this->hasCircularConceptDescription( $desc, $concept ) ) {
					return true;
				}
			}
		}

		return false;
	}

}
