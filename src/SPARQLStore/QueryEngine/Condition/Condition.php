<?php

namespace SMW\SPARQLStore\QueryEngine\Condition;

/**
 * Abstract class that represents a SPARQL (sub-)pattern and relevant pieces
 * of associated information for using it in query building.
 *
 * @ingroup SMWStore
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
abstract class Condition {

	/**
	 * If results could be ordered by the things that this condition
	 * matches, then this is the name of the variable to use in ORDER BY.
	 * Otherwise it is ''.
	 * @note SPARQL variable names do not include the initial "?" or "$".
	 * @var string
	 */
	public $orderByVariable = '';

	/**
	 * Array that relates sortkeys (given by the users, i.e. property
	 * names) to variable names in the generated SPARQL query.
	 * Format sortkey => variable name
	 * @var array
	 */
	public $orderVariables = array();

	/**
	 * Associative array of additional conditions that should not narrow
	 * down the set of results, but that introduce some relevant variable,
	 * typically for ordering. For instance, selecting the sortkey of a
	 * page needs only be done once per query. The array is indexed by the
	 * name of the (main) selected variable, e.g. "v42sortkey" to allow
	 * elimination of duplicate weak conditions that aim to introduce this
	 * variable.
	 * @var array of format "condition identifier" => "condition"
	 */
	public $weakConditions = array();

	/**
	 * Associative array of additional conditions that should can narrow
	 * down the set of results,
	 *
	 * @var array of format "condition identifier" => "condition"
	 */
	public $cogentConditions = array();

	/**
	 * Associative array of additional namespaces that this condition
	 * requires to be declared
	 * @var array of format "shortName" => "namespace URI"
	 */
	public $namespaces = array();

	/**
	 * Get the SPARQL condition string that this object represents. This
	 * does not inlcude the weak conditions, or additional formulations to
	 * match singletons (see SMWSparqlSingletonCondition).
	 *
	 * @return string
	 */
	abstract public function getCondition();

	/**
	 * Tell whether the condition string returned by getCondition() is safe
	 * in the sense that it can be used alone in a SPARQL query. This
	 * requires that all filtered variables occur in some graph pattern,
	 * and that the condition is not empty.
	 *
	 * @return boolean
	 */
	abstract public function isSafe();

	public function addNamespaces( array $namespaces ) {
		$this->namespaces = array_merge( $this->namespaces, $namespaces );
	}

	public function getWeakConditionString() {
		return implode( '', $this->weakConditions );
	}

	public function getCogentConditionString() {
		return implode( '', $this->cogentConditions );
	}

}
