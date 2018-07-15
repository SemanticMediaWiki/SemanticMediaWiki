<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Query\Language\Disjunction;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DisjunctionInterpreter {

	/**
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $conditionBuilder
	 */
	public function __construct( ConditionBuilder $conditionBuilder ) {
		$this->conditionBuilder = $conditionBuilder;
	}

	/**
	 * @since 3.0
	 *
	 * @param Disjunction $description
	 *
	 * @return Condition|[]
	 */
	public function interpretDescription( Disjunction $description, $isConjunction = false ) {

		$params = [];
		$notConditionFields = [];

		foreach ( $description->getDescriptions() as $desc ) {

			// Mark each as being part of a disjunction in order to to decide
			// whether a subquery should fail as part of a conjunction or not
			// when it relates to a disjunctive description
			// [[Foo.bar::123]] AND [[Foobar::123]] (fails) vs.
			// [[Foo.bar::123]] OR [[Foobar::123]]
			$desc->isPartOfDisjunction = true;

			if ( ( $param = $this->conditionBuilder->interpretDescription( $desc, true ) ) !== [] ) {

				// @see SomePropertyInterpreter
				// Collect a possible negation condition in case `must_not.property.exists`
				// is set (which is the SMW default mode) to allow wrapping an
				// additional condition around an OR when the existence of the
				// queried property is required
				if ( isset( $desc->notConditionField ) ) {
					$notConditionFields[] = $desc->notConditionField;
				}

				$params[] = $param;
			}
		}

		if ( $params === [] ) {
			return [];
		}

		$condition = $this->conditionBuilder->newCondition( $params );
		$condition->type( 'should' );

		$condition->log( [ 'Disjunction' => $description->getQueryString() ] );

		$notConditionFields = array_keys( array_flip( $notConditionFields ) );

		if ( $notConditionFields === [] ) {
			return $condition;
		}

		$existsConditions = [];
		$fieldMapper = $this->conditionBuilder->getFieldMapper();

		// Extra condition that satisfies !/OR condition (see T:Q0905#5 and
		// T:Q1106#4)
		//
		// Use case: `[[Category:E-Q1106]]<q>[[Has restricted status record::!~cl*]]
		// OR [[Has restricted status record::!~*in*]]</q>` and `[[Category:Q0905]]
		// [[!Example/Q0905/1]] <q>[[Has page::123]] OR [[Has page::!ABCD]]</q>`
		foreach ( $notConditionFields as $field ) {
			$existsConditions[] = $fieldMapper->exists( $field );
		}

		// We wrap the intermediary `should` clause in an extra `must` to ensure
		// those properties are exists for the returned documents.
		$condition = $this->conditionBuilder->newCondition( [ $condition, $existsConditions ] );
		$condition->type( 'must' );

		return $condition;
	}

}
