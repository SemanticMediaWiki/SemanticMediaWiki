<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Parser as QueryParser;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Options;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConceptDescriptionInterpreter {

	/**
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @var QueryParser
	 */
	private $queryParser;

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $conditionBuilder
	 * @param QueryParser $queryParser
	 */
	public function __construct( ConditionBuilder $conditionBuilder, QueryParser $queryParser ) {
		$this->conditionBuilder = $conditionBuilder;
		$this->queryParser = $queryParser;
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

		$value = $this->conditionBuilder->getStore()->getPropertyValues(
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

		$params = $this->conditionBuilder->interpretDescription(
			$description,
			$isConjunction
		);

		$params = $this->terms_lookup( $description, $concept, $params );

		$condition = $this->conditionBuilder->newCondition( $params );
		$condition->type( '' );

		$condition->log( [ 'ConceptDescription' => $description->getQueryString() ] );

		return $condition;
	}

	private function terms_lookup( $description, $concept, $params ) {

		$concept->setId(
			$this->conditionBuilder->getID( $concept )
		);

		$termsLookup = $this->conditionBuilder->getTermsLookup();

		$parameters = $termsLookup->newParameters(
			[
				'id' => $concept->getId(),
				'hash' => $concept->getHash(),
				'query.string' => $description->getQueryString(),
				'fingerprint' => $description->getFingerprint(),
				'params' => $params,
			]
		);

		// Using the terms lookup to prefetch IDs from the lookup index
		if ( $this->conditionBuilder->getOption( 'concept.terms.lookup' ) ) {
			$params = $termsLookup->lookup( 'concept', $parameters );
		}

		if ( $parameters->has( 'query.info' ) ) {
			$this->conditionBuilder->addQueryInfo( $parameters->get( 'query.info' ) );
		}

		return $params;
	}

	private function hasCircularConceptDescription( $description, $concept ) {

		if ( $description instanceof ConceptDescription ) {
			if ( $description->getConcept()->equals( $concept ) ) {
				$this->conditionBuilder->addError( [ 'smw-query-condition-circular', $description->getQueryString() ] );
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
