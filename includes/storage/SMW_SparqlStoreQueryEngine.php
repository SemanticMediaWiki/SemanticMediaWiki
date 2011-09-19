<?php

/**
 * Class mapping SMWDescription objects to SPARQL query conditions.
 *
 * @author Markus Krötzsch
 *
 * @file
 * @ingroup SMWStore
 */

/**
 * Abstract class that represents a SPARQL (sub-)pattern and relevant pieces
 * of associated information for using it in query building.
 *
 * @since 1.6
 *
 * @ingroup SMWStore
 */
abstract class SMWSparqlCondition {

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

	public function getWeakConditionString() {
		return implode( $this->weakConditions );
	}

}

/**
 * Represents a condition that cannot match anything.
 * Ordering is not relevant, as there is nothing to order.
 *
 * @ingroup SMWStore
 */
class SMWSparqlFalseCondition extends SMWSparqlCondition {

	public function getCondition() {
		return "<http://www.example.org> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#nothing> .\n";
	}

	public function isSafe() {
		return true;
	}
}

/**
 * Represents a condition that matches everything. Weak conditions (see
 * SMWSparqlCondition::$weakConditions) might be still be included to
 * enable ordering (selecting sufficient data to order by).
 *
 * @ingroup SMWStore
 */
class SMWSparqlTrueCondition extends SMWSparqlCondition {

	public function getCondition() {
		return '';
	}

	public function isSafe() {
		return false;
	}
}

/**
 * Container class that represents a SPARQL (sub-)pattern and relevant pieces
 * of associated information for using it in query building.
 *
 * @ingroup SMWStore
 */
class SMWSparqlWhereCondition extends SMWSparqlCondition {

	/**
	 * The pattern string. Anything that can be used as a WHERE condition
	 * when put between "{" and "}".
	 * @var string
	 */
	public $condition;

	/**
	 * Whether this condition is safe.
	 * @see SMWSparqlCondition::isSafe().
	 * @var boolean
	 */
	public $isSafe;

	public function __construct( $condition, $isSafe, $namespaces ) {
		$this->condition  = $condition;
		$this->isSafe     = $isSafe;
		$this->namespaces = $namespaces;
	}

	public function getCondition() {
		return $this->condition;
	}

	public function isSafe() {
		return $this->isSafe;
	}
}

/**
 * A SPARQL condition that can match only a single element, or nothing at all.
 *
 * @ingroup SMWStore
 */
class SMWSparqlSingletonCondition extends SMWSparqlCondition {

	/**
	 * Pattern string. Anything that can be used as a WHERE condition
	 * when put between "{" and "}". Can be empty if the result
	 * unconditionally is the given element.
	 * @var string
	 */
	public $condition;

	/**
	 * The single element that this condition may possibly match.
	 * @var SMWExpElement
	 */
	public $matchElement;

	/**
	 * Whether this condition is safe.
	 * @see SMWSparqlCondition::isSafe().
	 * @var boolean
	 */
	public $isSafe;

	public function __construct( SMWExpElement $matchElement, $condition = '', $isSafe = false, $namespaces = array() ) {
		$this->matchElement = $matchElement;
		$this->condition  = $condition;
		$this->isSafe     = $isSafe;
		$this->namespaces = $namespaces;
	}

	public function getCondition() {
		return $this->condition;
	}

	public function isSafe() {
		return $this->isSafe;
	}

}

/**
 * A SPARQL condition that consists in a FILTER term only (possibly with some
 * weak conditions to introduce the variables that the filter acts on).
 *
 * @ingroup SMWStore
 */
class SMWSparqlFilterCondition extends SMWSparqlCondition {

	/**
	 * Additional filter condition, i.e. a string that could be placed in
	 * "FILTER( ... )".
	 * @var string
	 */
	public $filter;

	public function __construct( $filter, $namespaces ) {
		$this->filter = $filter;
		$this->namespaces = $namespaces;
	}

	public function getCondition() {
		return "FILTER( {$this->filter} )\n";
	}

	public function isSafe() {
		return false;
	}

}


/**
 * Class mapping SMWQuery objects to SPARQL, and for controlling the execution
 * of these queries to obtain suitable SMWQueryResult objects.
 *
 * @ingroup SMWStore
 */
class SMWSparqlStoreQueryEngine {

	/// The name of the SPARQL variable that represents the query result.
	const RESULT_VARIABLE = 'result';

	/**
	 * Counter used to generate globally fresh variables.
	 * @var integer
	 */
	protected $m_variableCounter = 0;

	/**
	 * Copy of the SMWQuery sortkeys array to be used while building the
	 * SPARQL query conditions.
	 * @var array
	 */
	protected $m_sortkeys;

	/**
	 * The store that we work for.
	 * @var SMWStore
	 */
	protected $m_store;

	/**
	 * Constructor.
	 *
	 * @param $store SMWStore that this object will use
	 */
	public function __construct( SMWStore $store ) {
		$this->m_store = $store;
	}

	/**
	 * Get the output number for a query in counting mode.
	 *
	 * @param $query SMWQuery
	 * @return integer
	 */
	public function getCountQueryResult( SMWQuery $query ) {
		$this->m_sortkeys = array(); // ignore sorting, just count
		$sparqlCondition = $this->getSparqlCondition( $query->getDescription() );

		if ( $sparqlCondition instanceof SMWSparqlSingletonCondition ) {
			$matchElement = $sparqlCondition->matchElement;
			if ( $sparqlCondition->condition == '' ) { // all URIs exist, no querying
				return 1;
			} else {
				$condition = $this->getSparqlConditionString( $sparqlCondition );
				$namespaces = $sparqlCondition->namespaces;
				$askQueryResult = smwfGetSparqlDatabase()->ask( $condition, $namespaces );
				return $askQueryResult->isBooleanTrue() ? 1 : 0;
			}
		} elseif ( $sparqlCondition instanceof SMWSparqlFalseCondition ) {
			return 0;
		} else {
			//debug_zval_dump( $condition );
			$condition = $this->getSparqlConditionString( $sparqlCondition );
			$namespaces = $sparqlCondition->namespaces;
			$options = $this->getSparqlOptions( $query, $sparqlCondition );
			$options['DISTINCT'] = true;
			$sparqlResultWrapper = smwfGetSparqlDatabase()->selectCount( '?' . self::RESULT_VARIABLE,
			                                      $condition, $options, $namespaces );
			if ( $sparqlResultWrapper->getErrorCode() == SMWSparqlResultWrapper::ERROR_NOERROR ) {
				return (int)$sparqlResultWrapper->getNumericValue();
			} else {
				///@todo Implement error reporting for counting queries.
// 				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
// 				$result->addErrors( array( wfMsgForContent( 'smw_db_sparqlqueryproblem' ) ) );
			}
		}
	}

	/**
	 * Get the results for a query in instance retrieval mode.
	 *
	 * @param $query SMWQuery
	 * @return SMWQueryResult
	 */
	public function getInstanceQueryResult( SMWQuery $query ) {
		$this->m_sortkeys = $query->sortkeys;
		$sparqlCondition = $this->getSparqlCondition( $query->getDescription() );
		//debug_zval_dump($sparqlCondition);

		if ( $sparqlCondition instanceof SMWSparqlSingletonCondition ) {
			$matchElement = $sparqlCondition->matchElement;
			if ( $sparqlCondition->condition == '' ) { // all URIs exist, no querying
				$results = array( array ( $matchElement ) );
			} else {
				$condition = $this->getSparqlConditionString( $sparqlCondition );
				$namespaces = $sparqlCondition->namespaces;
				$askQueryResult = smwfGetSparqlDatabase()->ask( $condition, $namespaces );
				$results = $askQueryResult->isBooleanTrue() ? array( array ( $matchElement ) ) : array();
			}
			$sparqlResultWrapper = new SMWSparqlResultWrapper( array( self::RESULT_VARIABLE => 0 ), $results );
		} elseif ( $sparqlCondition instanceof SMWSparqlFalseCondition ) {
			$sparqlResultWrapper = new SMWSparqlResultWrapper( array( self::RESULT_VARIABLE => 0 ), array() );
		} else {
			//debug_zval_dump( $condition );
			$condition = $this->getSparqlConditionString( $sparqlCondition );
			$namespaces = $sparqlCondition->namespaces;
			$options = $this->getSparqlOptions( $query, $sparqlCondition );
			$options['DISTINCT'] = true;
			$sparqlResultWrapper = smwfGetSparqlDatabase()->select( '?' . self::RESULT_VARIABLE,
			                         $condition, $options, $namespaces );
		}

		//debug_zval_dump( $sparqlResultWrapper );
		return $this->getQueryResultFromSparqlResult( $sparqlResultWrapper, $query );
	}

	/**
	 * Get the output string for a query in debugging mode.
	 *
	 * @param $query SMWQuery
	 * @return string
	 */
	public function getDebugQueryResult( SMWQuery $query ) {
		$this->m_sortkeys = $query->sortkeys;
		$sparqlCondition = $this->getSparqlCondition( $query->getDescription() );

		$entries = array();

		if ( $sparqlCondition instanceof SMWSparqlSingletonCondition ) {
			$matchElement = $sparqlCondition->matchElement;
			if ( $sparqlCondition->condition == '' ) { // all URIs exist, no querying
				$sparql = 'None (no conditions).';
			} else {
				$condition = $this->getSparqlConditionString( $sparqlCondition );
				$namespaces = $sparqlCondition->namespaces;
				$sparql = smwfGetSparqlDatabase()->getSparqlForAsk( $condition, $namespaces );
			}
		} elseif ( $sparqlCondition instanceof SMWSparqlFalseCondition ) {
			$sparql = 'None (conditions can not be satisfied by anything).';
		} else {
			$condition = $this->getSparqlConditionString( $sparqlCondition );
			$namespaces = $sparqlCondition->namespaces;
			$options = $this->getSparqlOptions( $query, $sparqlCondition );
			$options['DISTINCT'] = true;
			$sparql = smwfGetSparqlDatabase()->getSparqlForSelect( '?' . self::RESULT_VARIABLE,
			                         $condition, $options, $namespaces );
		}
		$sparql = str_replace( array( '[',':',' ' ), array( '&#x005B;', '&#x003A;', '&#x0020;' ), $sparql );
		$entries['SPARQL Query'] = "<pre>$sparql</pre>";

		return SMWStore::formatDebugOutput( 'SMWSparqlStore', $entries, $query );
	}

	/**
	 * Build the condition (WHERE) string for a given SMWSparqlCondition.
	 * The function also expresses the single value of
	 * SMWSparqlSingletonCondition objects in the condition, which may
	 * lead to additional namespaces for serializing its URI.
	 *
	 * @param $sparqlCondition SMWSparqlCondition
	 * @return string
	 */
	protected function getSparqlConditionString( SMWSparqlCondition &$sparqlCondition ) {
		$condition = $sparqlCondition->getWeakConditionString();
		if ( ( $condition == '' ) && !$sparqlCondition->isSafe() ) {
			$swivtPageResource = SMWExporter::getSpecialNsResource( 'swivt', 'page' );
			$condition = '?' . self::RESULT_VARIABLE . ' ' . $swivtPageResource->getQName() . " ?url .\n";
		}
		$condition .= $sparqlCondition->getCondition();

		if ( $sparqlCondition instanceof SMWSparqlSingletonCondition ) { // prepare for ASK, maybe rather use BIND?
			$matchElement = $sparqlCondition->matchElement;
			$matchElementName = SMWTurtleSerializer::getTurtleNameForExpElement( $matchElement );
			if ( $matchElement instanceof SMWExpNsResource ) {
				$sparqlCondition->namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
			}
			$condition = str_replace( '?' . self::RESULT_VARIABLE . ' ', "$matchElementName ", $condition );
		}

		return $condition;
	}

	/**
	 * Build an SMWQueryResult object from a SMWSparqlResultWrapper. This
	 * function is used to generate instance query results, and the given
	 * result wrapper must have an according format (one result column that
	 * contains URIs of wiki pages).
	 *
	 * @param $sparqlResultWrapper SMWSparqlResultWrapper
	 * @param $query SMWQuery, SMWQueryResults hold a reference to original query
	 * @return SMWQueryResult
	 */
	protected function getQueryResultFromSparqlResult( SMWSparqlResultWrapper $sparqlResultWrapper, SMWQuery $query ) {
		$resultDataItems = array();
		foreach ( $sparqlResultWrapper as $resultRow ) {
			if ( count( $resultRow ) > 0 ) {
				$dataItem = SMWExporter::findDataItemForExpElement( $resultRow[0] );
				if ( $dataItem !== null ) {
					$resultDataItems[] = $dataItem;
				}
			}
		}

		if ( $sparqlResultWrapper->numRows() > $query->getLimit() ) {
			array_pop( $resultDataItems );
			$hasFurtherResults = true;
		} else {
			$hasFurtherResults = false;
		}

		$result = new SMWQueryResult(  $query->getDescription()->getPrintrequests(), $query, $resultDataItems, $this->m_store, $hasFurtherResults );

		switch ( $sparqlResultWrapper->getErrorCode() ) {
			case SMWSparqlResultWrapper::ERROR_NOERROR: break;
			case SMWSparqlResultWrapper::ERROR_INCOMPLETE:
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$result->addErrors( array( wfMsgForContent( 'smw_db_sparqlqueryincomplete' ) ) );
			break;
			default:
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$result->addErrors( array( wfMsgForContent( 'smw_db_sparqlqueryproblem' ) ) );
			break;
		}

		return $result;
	}

	/**
	 * Get a SMWSparqlCondition object for an SMWDescription.
	 * This conversion is implemented by a number of recursive functions,
	 * and this is the main entry point for this recursion. In particular,
	 * it resets global variables that are used for the construction.
	 *
	 * If property value variables should be recorded for ordering results
	 * later on, the keys of the respective properties need to be given in
	 * m_sortkeys earlier.
	 *
	 * @param $description SMWDescription
	 * @return SMWSparqlCondition
	 */
	protected function getSparqlCondition( SMWDescription $description ) {
		$this->m_variableCounter = 0;
		$sparqlCondition = $this->buildSparqlCondition( $description, self::RESULT_VARIABLE, null );
		$this->addMissingOrderByConditions( $sparqlCondition );
		return $sparqlCondition;
	}

	/**
	 * Recursively create an SMWSparqlCondition from an SMWDescription.
	 *
	 * @param $description SMWDescription
	 * @param $joinVariable string name of the variable that conditions
	 * will refer to
	 * @param $orderByProperty mixed SMWDIProperty or null, if given then
	 * this is the property the values of which this condition will refer
	 * to, and the condition should also enable ordering by this value
	 * @return SMWSparqlCondition
	 */
	protected function buildSparqlCondition( SMWDescription $description, $joinVariable, $orderByProperty ) {
		if ( $description instanceof SMWSomeProperty ) {
			return $this->buildPropertyCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof SMWNamespaceDescription ) {
			return $this->buildNamespaceCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof SMWConjunction ) {
			return $this->buildConjunctionCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof SMWDisjunction ) {
			return $this->buildDisjunctionCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof SMWClassDescription ) {
			return $this->buildClassCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof SMWValueDescription ) {
			return $this->buildValueCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof SMWConceptDescription ) {
			return new SMWSparqlTrueCondition(); ///TODO Implement concept queries
		} else { // (e.g. SMWThingDescription)
			return $this->buildTrueCondition( $joinVariable, $orderByProperty );
		}
	}

	/**
	 * Recursively create an SMWSparqlCondition from an SMWConjunction.
	 *
	 * @param $description SMWConjunction
	 * @param $joinVariable string name, see buildSparqlCondition()
	 * @param $orderByProperty mixed SMWDIProperty or null, see buildSparqlCondition()
	 * @return SMWSparqlCondition
	 */
	protected function buildConjunctionCondition( SMWConjunction $description, $joinVariable, $orderByProperty ) {
		$subDescriptions = $description->getDescriptions();
		if ( count( $subDescriptions ) == 0 ) { // empty conjunction: true
			return $this->buildTrueCondition( $joinVariable, $orderByProperty );
		} elseif ( count( $subDescriptions ) == 1 ) { // conjunction with one element
			return $this->buildSparqlCondition( reset( $subDescriptions ), $joinVariable, $orderByProperty );
		}

		$condition = '';
		$filter = '';
		$namespaces = $weakConditions = $orderVariables = array();
		$singletonMatchElement = null;
		$singletonMatchElementName = '';
		$hasSafeSubconditions = false;
		foreach ( $subDescriptions as $subDescription ) {
			$subCondition = $this->buildSparqlCondition( $subDescription, $joinVariable, null );
			if ( $subCondition instanceof SMWSparqlFalseCondition ) {
				return new SMWSparqlFalseCondition();
			} elseif ( $subCondition instanceof SMWSparqlTrueCondition ) {
				// ignore true conditions in a conjunction
			} elseif ( $subCondition instanceof SMWSparqlWhereCondition ) {
				$condition .= $subCondition->condition;
			} elseif ( $subCondition instanceof SMWSparqlFilterCondition ) {
				$filter .= ( $filter ? ' && ' : '' ) . $subCondition->filter;
			} elseif ( $subCondition instanceof SMWSparqlSingletonCondition ) {
				$matchElement = $subCondition->matchElement;
				$matchElementName = SMWTurtleSerializer::getTurtleNameForExpElement( $matchElement );
				if ( $matchElement instanceof SMWExpNsResource ) {
					$namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
				}

				if ( ( $singletonMatchElement !== null ) &&
				     ( $singletonMatchElementName !== $matchElementName ) ) {
					return new SMWSparqlFalseCondition();
				}

				$condition .= $subCondition->condition;
				$singletonMatchElement = $subCondition->matchElement;
				$singletonMatchElementName = $matchElementName;
			}
			$hasSafeSubconditions = $hasSafeSubconditions || $subCondition->isSafe();
			$namespaces = array_merge( $namespaces, $subCondition->namespaces );
			$weakConditions = array_merge( $weakConditions, $subCondition->weakConditions );
			$orderVariables = array_merge( $orderVariables, $subCondition->orderVariables );
		}

		if ( $singletonMatchElement !== null ) {
			if ( $filter != '' ) {
				$condition .= "FILTER( $filter )";
			}
			$result = new SMWSparqlSingletonCondition( $singletonMatchElement, $condition,
			                                           $hasSafeSubconditions, $namespaces );
		} elseif ( $condition == '' ) {
			$result = new SMWSparqlFilterCondition( $filter, $namespaces );
		} else {
			if ( $filter != '' ) {
				$condition .= "FILTER( $filter )";
			}
			$result = new SMWSparqlWhereCondition( $condition, $hasSafeSubconditions, $namespaces );
		}

		$result->weakConditions = $weakConditions;
		$result->orderVariables = $orderVariables;

		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty );

		return $result;
	}

	/**
	 * Recursively create an SMWSparqlCondition from an SMWDisjunction.
	 *
	 * @param $description SMWDisjunction
	 * @param $joinVariable string name, see buildSparqlCondition()
	 * @param $orderByProperty mixed SMWDIProperty or null, see buildSparqlCondition()
	 * @return SMWSparqlCondition
	 */
	protected function buildDisjunctionCondition( SMWDisjunction $description, $joinVariable, $orderByProperty ) {
		$subDescriptions = $description->getDescriptions();
		if ( count( $subDescriptions ) == 0 ) { // empty disjunction: false
			return new SMWSparqlFalseCondition();
		} elseif ( count( $subDescriptions ) == 1 ) { // disjunction with one element
			return $this->buildSparqlCondition( reset( $subDescriptions ), $joinVariable, $orderByProperty );
		} // else: proper disjunction; note that orderVariables found in subconditions cannot be used for the whole disjunction

		$unionCondition = '';
		$filter = '';
		$namespaces = $weakConditions = array();
		$hasSafeSubconditions = false;
		foreach ( $subDescriptions as $subDescription ) {
			$subCondition = $this->buildSparqlCondition( $subDescription, $joinVariable, null );
			if ( $subCondition instanceof SMWSparqlFalseCondition ) {
				// empty parts in a disjunction can be ignored
			} elseif ( $subCondition instanceof SMWSparqlTrueCondition ) {
				return  $this->buildTrueCondition( $joinVariable, $orderByProperty );
			} elseif ( $subCondition instanceof SMWSparqlWhereCondition ) {
				$hasSafeSubconditions = $hasSafeSubconditions || $subCondition->isSafe();
				$unionCondition .= ( $unionCondition ? ' UNION ' : '' ) .
				                   "{\n" . $subCondition->condition . "}";
			} elseif ( $subCondition instanceof SMWSparqlFilterCondition ) {
				$filter .= ( $filter ? ' || ' : '' ) . $subCondition->filter;
			} elseif ( $subCondition instanceof SMWSparqlSingletonCondition ) {
				$hasSafeSubconditions = $hasSafeSubconditions || $subCondition->isSafe();
				$matchElement = $subCondition->matchElement;
				$matchElementName = SMWTurtleSerializer::getTurtleNameForExpElement( $matchElement );
				if ( $matchElement instanceof SMWExpNsResource ) {
					$namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
				}
				if ( $subCondition->condition == '' ) {
					$filter .= ( $filter ? ' || ' : '' ) . "?$joinVariable = $matchElementName";
				} else {
					$unionCondition .= ( $unionCondition ? ' UNION ' : '' ) .
				                   "{\n" . $subCondition->condition . " FILTER( ?$joinVariable = $matchElementName ) }";
				}
			}
			$namespaces = array_merge( $namespaces, $subCondition->namespaces );
			$weakConditions = array_merge( $weakConditions, $subCondition->weakConditions );
		}

		if ( ( $unionCondition == '' ) && ( $filter == '' ) ) {
			return new SMWSparqlFalseCondition();
		} elseif ( $unionCondition == '' ) {
			$result = new SMWSparqlFilterCondition( $filter, $namespaces );
		} elseif ( $filter == '' ) {
			$result = new SMWSparqlWhereCondition( $unionCondition, $hasSafeSubconditions, $namespaces );
		} else {
			$subJoinVariable = $this->getNextVariable();
			$unionCondition = str_replace( "?$joinVariable ", "?$subJoinVariable ", $unionCondition );
			$filter .= " || ?$joinVariable = ?$subJoinVariable";
			$result = new SMWSparqlWhereCondition( "OPTIONAL { $unionCondition }\n FILTER( $filter )\n", false, $namespaces );
		}

		$result->weakConditions = $weakConditions;

		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty );

		return $result;

	}

	/**
	 * Recursively create an SMWSparqlCondition from an SMWSomeProperty.
	 *
	 * @param $description SMWSomeProperty
	 * @param $joinVariable string name, see buildSparqlCondition()
	 * @param $orderByProperty mixed SMWDIProperty or null, see buildSparqlCondition()
	 * @return SMWSparqlCondition
	 */
	protected function buildPropertyCondition( SMWSomeProperty $description, $joinVariable, $orderByProperty ) {
		$diProperty = $description->getProperty();

		//*** Find out if we should order by the values of this property ***//
		if ( array_key_exists( $diProperty->getKey(), $this->m_sortkeys ) ) {
			$innerOrderByProperty = $diProperty;
		} else {
			$innerOrderByProperty = null;
		}

		//*** Prepare inner condition ***//
		$innerJoinVariable = $this->getNextVariable();
		$innerCondition = $this->buildSparqlCondition( $description->getDescription(), $innerJoinVariable, $innerOrderByProperty );
		$namespaces = $innerCondition->namespaces;

		if ( $innerCondition instanceof SMWSparqlFalseCondition ) {
			return new SMWSparqlFalseCondition();
		} elseif ( $innerCondition instanceof SMWSparqlSingletonCondition ) {
			$matchElement = $innerCondition->matchElement;
			$objectName = SMWTurtleSerializer::getTurtleNameForExpElement( $matchElement );
			if ( $matchElement instanceof SMWExpNsResource ) {
				$namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
			}
		} else {
			$objectName = '?' . $innerJoinVariable;
		}

		//*** Exchange arguments when property is inverse ***//
		if ( $diProperty->isInverse() ) { // don't check if this really makes sense
			$subjectName = $objectName;
			$objectName = '?' . $joinVariable;
		} else {
			$subjectName = '?' . $joinVariable;
		}

		//*** Build the condition ***//
		$typeId = $diProperty->findPropertyTypeID();
		$diType = SMWDataValueFactory::getDataItemId( $typeId );
		if ( SMWExporter::hasHelperExpElement( $diType ) ) {
			$propertyExpElement = SMWExporter::getResourceElementForProperty( $diProperty, true );
		} else {
			$propertyExpElement = SMWExporter::getResourceElementForProperty( $diProperty );
		}
		$propertyName = SMWTurtleSerializer::getTurtleNameForExpElement( $propertyExpElement );
		if ( $propertyExpElement instanceof SMWExpNsResource ) {
			$namespaces[$propertyExpElement->getNamespaceId()] = $propertyExpElement->getNamespace();
		}
		$condition = "$subjectName $propertyName $objectName .\n";
		$innerConditionString = $innerCondition->getCondition() . $innerCondition->getWeakConditionString();
		if ( $innerConditionString != '' ) {
			$condition .= "{ $innerConditionString}\n" ;
		}
		$result = new SMWSparqlWhereCondition( $condition, true, $namespaces );

		//*** Record inner ordering variable if found ***//
		$result->orderVariables = $innerCondition->orderVariables;
		if ( ( $innerOrderByProperty !== null ) && ( $innerCondition->orderByVariable != '' ) ) {
			$result->orderVariables[$diProperty->getKey()] = $innerCondition->orderByVariable;
		}

		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty, SMWDataItem::TYPE_WIKIPAGE );

		return $result;
	}

	/**
	 * Create an SMWSparqlCondition from an SMWClassDescription.
	 *
	 * @param $description SMWClassDescription
	 * @param $joinVariable string name, see buildSparqlCondition()
	 * @param $orderByProperty mixed SMWDIProperty or null, see buildSparqlCondition()
	 * @return SMWSparqlCondition
	 */
	protected function buildClassCondition( SMWClassDescription $description, $joinVariable, $orderByProperty ) {
		$condition = '';
		$namespaces = array();
		$instExpElement = SMWExporter::getSpecialPropertyResource( '_INST' );
		foreach( $description->getCategories() as $diWikiPage ) {
			$categoryExpElement = SMWExporter::getResourceElementForWikiPage( $diWikiPage );
			$categoryName = SMWTurtleSerializer::getTurtleNameForExpElement( $categoryExpElement );
			$namespaces[$categoryExpElement->getNamespaceId()] = $categoryExpElement->getNamespace();
			$newcondition = "{ ?$joinVariable " . $instExpElement->getQName() . " $categoryName . }\n";
			if ( $condition == '' ) {
				$condition = $newcondition;
			} else {
				$condition .= "UNION\n$newcondition";
			}
		}

		if ( $condition == '' ) { // empty disjunction: always false, no results to order
			return new SMWSparqlFalseCondition();
		}

		$result = new SMWSparqlWhereCondition( $condition, true, $namespaces );

		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty, SMWDataItem::TYPE_WIKIPAGE );

		return $result;
	}

	/**
	 * Create an SMWSparqlCondition from an SMWNamespaceDescription.
	 *
	 * @param $description SMWNamespaceDescription
	 * @param $joinVariable string name, see buildSparqlCondition()
	 * @param $orderByProperty mixed SMWDIProperty or null, see buildSparqlCondition()
	 * @return SMWSparqlCondition
	 */
	protected function buildNamespaceCondition( SMWNamespaceDescription $description, $joinVariable, $orderByProperty ) {
		$nspropExpElement = SMWExporter::getSpecialNsResource( 'swivt', 'wikiNamespace' );
		$nsExpElement = new SMWExpLiteral( $description->getNamespace(), 'http://www.w3.org/2001/XMLSchema#integer' );
		$nsName = SMWTurtleSerializer::getTurtleNameForExpElement( $nsExpElement );
		$condition = "{ ?$joinVariable " . $nspropExpElement->getQName() . " $nsName . }\n";

		$result = new SMWSparqlWhereCondition( $condition, true, array() );

		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty, SMWDataItem::TYPE_WIKIPAGE );

		return $result;
	}

	/**
	 * Create an SMWSparqlCondition from an SMWValueDescription.
	 *
	 * @param $description SMWValueDescription
	 * @param $joinVariable string name, see buildSparqlCondition()
	 * @param $orderByProperty mixed SMWDIProperty or null, see buildSparqlCondition()
	 * @return SMWSparqlCondition
	 */
	protected function buildValueCondition( SMWValueDescription $description, $joinVariable, $orderByProperty ) {
		$dataItem = $description->getDataItem();

		switch ( $description->getComparator() ) {
			case SMW_CMP_EQ:   $comparator = '='; break;
			case SMW_CMP_LESS: $comparator = '<'; break;
			case SMW_CMP_GRTR: $comparator = '>'; break;
			case SMW_CMP_LEQ:  $comparator = '<='; break;
			case SMW_CMP_GEQ:  $comparator = '>='; break;
			case SMW_CMP_NEQ:  $comparator = '!='; break;
			case SMW_CMP_LIKE: $comparator = 'regex'; break;
			case SMW_CMP_NLKE: $comparator = '!regex'; break;
			default:           $comparator = ''; // unkown, unsupported
		}

		if ( $comparator == '' ) {
			$result = $this->buildTrueCondition( $joinVariable, $orderByProperty );
		} elseif ( $comparator == '=' ) {
			$expElement = SMWExporter::getDataItemHelperExpElement( $dataItem );
			if ( $expElement === null ) {
				$expElement = SMWExporter::getDataItemExpElement( $dataItem, null );
			}
			$result = new SMWSparqlSingletonCondition( $expElement );
			$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty, $dataItem->getDIType() );
		} elseif ( $comparator == 'regex' || $comparator == '!regex' ) {
			if ( $dataItem instanceof SMWDIBlob ) {
				$pattern = '^' . str_replace( array( '^', '.', '\\', '+', '{', '}', '(', ')', '|', '^', '$', '[', ']', '*', '?' ),
				                              array( '\^', '\.', '\\\\', '\+', '\{', '\}', '\(', '\)', '\|', '\^', '\$', '\[', '\]', '.*', '.' ),
				                              $dataItem->getString() ) . '$';
				$result = new SMWSparqlFilterCondition( "$comparator( ?$joinVariable, \"$pattern\", \"s\")", array() );
				$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty, $dataItem->getDIType() );
			} else {
				$result = $this->buildTrueCondition( $joinVariable, $orderByProperty );
			}
		} else {
			$result = new SMWSparqlFilterCondition( '', array() );
			$this->addOrderByData( $result, $joinVariable, $dataItem->getDIType() );
			$orderByVariable = $result->orderByVariable;

			if ( $dataItem instanceof SMWDIWikiPage ) {
				$expElement = SMWExporter::getDataItemExpElement( $dataItem->getSortKeyDataItem(), null );
			} else {
				$expElement = SMWExporter::getDataItemHelperExpElement( $dataItem );
				if ( $expElement === null ) {
					$expElement = SMWExporter::getDataItemExpElement( $dataItem, null );
				}
			}

			$valueName = SMWTurtleSerializer::getTurtleNameForExpElement( $expElement );
			if ( $expElement instanceof SMWExpNsResource ) {
				$result->namespaces[$expElement->getNamespaceId()] = $expElement->getNamespace();
			}
			$result->filter = "?$orderByVariable $comparator $valueName";
		}

		return $result;
	}

	/**
	 * Create an SMWSparqlCondition from an empty (true) description.
	 * May still require helper conditions for ordering.
	 *
	 * @param $joinVariable string name, see buildSparqlCondition()
	 * @param $orderByProperty mixed SMWDIProperty or null, see buildSparqlCondition()
	 * @return SMWSparqlCondition
	 */
	protected function buildTrueCondition( $joinVariable, $orderByProperty ) {
		$result = new SMWSparqlTrueCondition();
		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty );
		return $result;
	}

	/**
	 * Get a fresh unused variable name for building SPARQL conditions.
	 *
	 * @return string
	 */
	protected function getNextVariable() {
		return 'v' . ( ++$this->m_variableCounter );
	}

	/**
	 * Extend the given SPARQL condition by a suitable order by variable,
	 * if an order by property is set.
	 *
	 * @param SMWSparqlCondition $sparqlCondition condition to modify
	 * @param string $mainVariable the variable that represents the value to be ordered
	 * @param mixed $orderByProperty SMWDIProperty or null
	 * @param integer $diType DataItem type id if known, or SMWDataItem::TYPE_NOTYPE to determine it from the property
	 */
	protected function addOrderByDataForProperty( SMWSparqlCondition &$sparqlCondition, $mainVariable, $orderByProperty, $diType = SMWDataItem::TYPE_NOTYPE ) {
		if ( $orderByProperty === null ) {
			return;
		}

		if ( $diType == SMWDataItem::TYPE_NOTYPE ) {
			$typeId = $orderByProperty->findPropertyTypeID();
			$diType = SMWDataValueFactory::getDataItemId( $typeId );
		}

		$this->addOrderByData( $sparqlCondition, $mainVariable, $diType );
	}

	/**
	 * Extend the given SPARQL condition by a suitable order by variable,
	 * possibly adding conditions if required for the type of data.
	 *
	 * @param SMWSparqlCondition $sparqlCondition condition to modify
	 * @param string $mainVariable the variable that represents the value to be ordered
	 * @param integer $diType DataItem type id
	 */
	protected function addOrderByData( SMWSparqlCondition &$sparqlCondition, $mainVariable, $diType ) {
		if ( $diType == SMWDataItem::TYPE_WIKIPAGE ) {
			$sparqlCondition->orderByVariable = $mainVariable . 'sk';
			$skeyExpElement = SMWExporter::getSpecialPropertyResource( '_SKEY' );
			$sparqlCondition->weakConditions = array( $sparqlCondition->orderByVariable =>
			      "?$mainVariable " . $skeyExpElement->getQName() . " ?{$sparqlCondition->orderByVariable} .\n" );
		} else {
			$sparqlCondition->orderByVariable = $mainVariable;
		}
	}

	/**
	 * Extend the given SMWSparqlCondition with additional conditions to
	 * ensure that it can be ordered by all requested properties. After
	 * this operation, every key in m_sortkeys is assigned to a query
	 * variable by $sparqlCondition->orderVariables.
	 *
	 * @param SMWSparqlCondition $sparqlCondition condition to modify
	 */
	protected function addMissingOrderByConditions( SMWSparqlCondition &$sparqlCondition ) {
		foreach ( $this->m_sortkeys as $propkey => $order ) {
			if ( !array_key_exists( $propkey, $sparqlCondition->orderVariables ) ) { // Find missing property to sort by.
				if ( $propkey == '' ) { // order by result page sortkey
					$this->addOrderByData( $sparqlCondition, self::RESULT_VARIABLE, SMWDataItem::TYPE_WIKIPAGE );
					$sparqlCondition->orderVariables[$propkey] = $sparqlCondition->orderByVariable;
				} else { // extend query to order by other property values
					$diProperty = new SMWDIProperty( $propkey );
					$auxDescription = new SMWSomeProperty( $diProperty, new SMWThingDescription() );
					$auxSparqlCondition = $this->buildSparqlCondition( $auxDescription, self::RESULT_VARIABLE, null );
					// orderVariables MUST be set for $propkey -- or there is a bug; let it show!
					$sparqlCondition->orderVariables[$propkey] = $auxSparqlCondition->orderVariables[$propkey];
					$sparqlCondition->weakConditions[$sparqlCondition->orderVariables[$propkey]] = $auxSparqlCondition->getWeakConditionString() . $auxSparqlCondition->getCondition();
				}
			}
		}
	}

	/**
	 * Get a SPARQL option array for the given query.
	 *
	 * @param SMWQuery $query
	 * @param SMWSparqlCondition $sparqlCondition (storing order by variable names)
	 * @return array
	 */
	protected function getSparqlOptions( SMWQuery $query, SMWSparqlCondition $sparqlCondition ) {
		global $smwgQSortingSupport, $smwgQRandSortingSupport;

		$result = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );

		// Build ORDER BY options using discovered sorting fields.
		if ( $smwgQSortingSupport ) {
			$orderByString = '';
			foreach ( $this->m_sortkeys as $propkey => $order ) {
				if ( ( $order != 'RANDOM' ) && array_key_exists( $propkey, $sparqlCondition->orderVariables ) ) {
					$orderByString .= "$order(?" . $sparqlCondition->orderVariables[$propkey] . ") ";
				} elseif ( ( $order == 'RANDOM' ) && $smwgQRandSortingSupport ) {
					// not supported in SPARQL; might be possible via function calls in some stores
				}
			}
			if ( $orderByString != '' ) {
				$result['ORDER BY'] = $orderByString;
			}
		}
		return $result;
	}

}