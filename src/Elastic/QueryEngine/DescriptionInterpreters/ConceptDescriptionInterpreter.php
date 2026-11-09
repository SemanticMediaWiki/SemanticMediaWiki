<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Elastic\QueryEngine\Condition;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Parser as QueryParser;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ConceptDescriptionInterpreter {

	/**
	 * Concepts currently being expanded on the active recursion branch, keyed by
	 * hash, used to break reference cycles between concepts.
	 */
	private array $activeConcepts = [];

	/**
	 * @since 3.0
	 */
	public function __construct(
		private readonly ConditionBuilder $conditionBuilder,
		private readonly QueryParser $queryParser,
	) {
	}

	/**
	 * @since 3.0
	 */
	public function interpretDescription(
		ConceptDescription $description,
		bool $isConjunction = false
	): array|Condition {
		$concept = $description->getConcept();

		// Break mutual/transitive concept cycles (for example A -> B -> A). The
		// `hasCircularConceptDescription` check below only catches a concept that
		// references itself directly, so re-entering a concept already being
		// expanded on this branch would otherwise recurse until memory is
		// exhausted.
		$hash = $concept->getHash();

		if ( isset( $this->activeConcepts[$hash] ) ) {
			$this->conditionBuilder->addError( [ 'smw-query-condition-circular', $description->getQueryString() ] );
			return [];
		}

		$value = $this->conditionBuilder->getStore()->getPropertyValues(
			$concept,
			new Property( '_CONC' )
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

		$this->activeConcepts[$hash] = true;

		try {
			$params = $this->conditionBuilder->interpretDescription(
				$description,
				$isConjunction
			);

			$params = $this->terms_lookup( $description, $concept, $params );

			$condition = $this->conditionBuilder->newCondition( $params );
			$condition->type( '' );

			$condition->log( [ 'ConceptDescription' => $description->getQueryString() ] );

			return $condition;
		} finally {
			unset( $this->activeConcepts[$hash] );
		}
	}

	private function terms_lookup( $description, WikiPage $concept, $params ) {
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

	private function hasCircularConceptDescription( $description, $concept ): bool {
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
