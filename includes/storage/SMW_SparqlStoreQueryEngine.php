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
	 * Associative array of additional conditions that should not narrow
	 * down the set of results, but that introduce some relevant variable,
	 * typically for ordering. For instance, selecting the sortkey of a
	 * page needs only be done once per query. The array is indexed by the
	 * name of the (main) selected variable, e.g. "v42sortkey" to allow
	 * elimination of duplicate weak conditions that aimm to introduce this
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

	/**
	 * Counter used to generate globally fresh variables.
	 * @var integer
	 */
	protected $m_variableCounter = 0;

	/**
	 * The store that we work for.
	 * @var SMWStore
	 */
	protected $m_store;

	public function __construct( SMWStore $store ) {
		$this->m_store = $store;
	}

	public function getInstanceQueryResult( SMWQuery $query ) {
		$sparqlCondition = $this->getSparqlCondition( $query->getDescription() );

		//debug_zval_dump($sparqlCondition);
		$namespaces = $sparqlCondition->namespaces;
		$condition = $sparqlCondition->getWeakConditionString();
		if ( ( $condition == '' ) && !$sparqlCondition->isSafe() ) {
			$swivtPageResource = SMWExporter::getSpecialNsResource( 'swivt', 'page' );
			$condition = '?result ' . $swivtPageResource->getQName() . " ?url .\n";
		}
		$condition .= $sparqlCondition->getCondition();

		if ( $sparqlCondition instanceof SMWSparqlSingletonCondition ) {
			// TODO use ASK to handle this
			$matchElement = $sparqlCondition->matchElement;
			if ( $sparqlCondition->condition == '' ) {
				$results = array( array ( $matchElement ) );
			} else {
				$matchElementName = SMWTurtleSerializer::getTurtleNameForExpElement( $matchElement );
				if ( $matchElement instanceof SMWExpNsResource ) {
					$namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
				}
				$condition = str_replace( '?result ', "$matchElementName ", $condition );
				$askQueryResult = smwfGetSparqlDatabase()->ask( $condition, $namespaces );
				if ( $askQueryResult->isBooleanTrue() ) {
					$results = array( array ( $matchElement ) );
				} else {
					$results = array();
				}
			}
			$sparqlResult = new SMWSparqlResultWrapper( array( 'result' => 0 ), $results ); //TODO
		} elseif ( $sparqlCondition instanceof SMWSparqlFalseCondition ) {
			$sparqlResult = new SMWSparqlResultWrapper( array( 'result' => 0 ), array() );
		} else {
			//debug_zval_dump( $condition );
			$options = $this->getSparqlOptions( $query );
			$options['DISTINCT'] = true;
			$sparqlResult = smwfGetSparqlDatabase()->select( '?result',
			                $condition, $options, $namespaces );
		}

		//debug_zval_dump( $sparqlResult );
		$resultDataItems = array();
		foreach ( $sparqlResult as $resultRow ) {
			if ( count( $resultRow ) > 0 ) {
				$dataItem = SMWExporter::findDataItemForExpElement( $resultRow[0] );
				if ( $dataItem !== null ) {
					$resultDataItems[] = $dataItem;
				}
			}
		}
		if ( count( $resultDataItems ) > $query->getLimit() ) {
			array_pop( $resultDataItems );
			$hasFurtherResults = true;
		} else {
			$hasFurtherResults = false;
		}

		$result = new SMWQueryResult(  $query->getDescription()->getPrintrequests(), $query, $resultDataItems, $this->m_store, $hasFurtherResults );
		if ( $sparqlResult->getErrorCode() != SMWSparqlResultWrapper::ERROR_NOERROR ) {
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$result->addErrors( array( wfMsgForContent( 'smw_db_sparqlqueryproblem' ) ) );
		}
		return $result;
	}

	public function getSparqlCondition( SMWDescription $description ) {
		$this->m_variableCounter = 0;
		$sparqlCondition = $this->buildSparqlCondition( $description, 'result', null );
		return $sparqlCondition;
	}

	/**
	 * Create an SMWSparqlCondition from the given SMWDescription.
	 */
	protected function buildSparqlCondition( SMWDescription $description, $joinVariable, $orderByProperty ) {
		if ( $description instanceof SMWSomeProperty ) {
			return $this->buildPropertyCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof SMWNamespaceDescription ) {
			return new SMWSparqlTrueCondition(); ///TODO Implement namespace filtering
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
			return new SMWSparqlTrueCondition();
		}
	}

	protected function buildConjunctionCondition( SMWConjunction $description, $joinVariable, $orderByProperty ) {
		$subDescriptions = $description->getDescriptions();
		if ( count( $subDescriptions ) == 0 ) { // empty conjunction: true
			/// FIXME Ordering is missing
			return new SMWSparqlTrueCondition();
		} elseif ( count( $subDescriptions ) == 1 ) { // conjunction with one element
			return $this->buildSparqlCondition( reset( $subDescriptions ), $joinVariable, $orderByProperty );
		}

		$condition = '';
		$filter = '';
		$namespaces = $weakConditions = array();
		$singletonMatchElement = null;
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

		//*** Possibly add conditions for ordering ***//
		if ( $orderByProperty !== null ) {
			/// TODO Depends on datatype
// 			$result->orderByVariable = $joinVariable . 'sk';
// 			$skeyExpElement = SMWExporter::getSpecialPropertyResource( '_SKEY' );
// 			$result->weakConditions = array( $result->orderByVariable => "?$joinVariable " . $skeyExpElement->getQName() . " ?{$result->orderByVariable} .\n" );
		}

		return $result;
	}

	protected function buildDisjunctionCondition( SMWDisjunction $description, $joinVariable, $orderByProperty ) {
		$subDescriptions = $description->getDescriptions();
		if ( count( $subDescriptions ) == 0 ) { // empty disjunction: false
			return new SMWSparqlFalseCondition();
		} elseif ( count( $subDescriptions ) == 1 ) { // disjunction with one element
			return $this->buildSparqlCondition( reset( $subDescriptions ), $joinVariable, $orderByProperty );
		}

		$unionCondition = '';
		$filter = '';
		$namespaces = $weakConditions = array();
		$hasSafeSubconditions = false;
		foreach ( $subDescriptions as $subDescription ) {
			$subCondition = $this->buildSparqlCondition( $subDescription, $joinVariable, null );
			if ( $subCondition instanceof SMWSparqlFalseCondition ) {
				// empty parts in a disjunction can be ignored
			} elseif ( $subCondition instanceof SMWSparqlTrueCondition ) {
				/// FIXME Take ordering into account.
				return new SMWSparqlTrueCondition();
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

		//*** Possibly add conditions for ordering ***//
		if ( $orderByProperty !== null ) {
			/// TODO Depends on datatype
// 			$result->orderByVariable = $joinVariable . 'sk';
// 			$skeyExpElement = SMWExporter::getSpecialPropertyResource( '_SKEY' );
// 			$result->weakConditions = array( $result->orderByVariable => "?$joinVariable " . $skeyExpElement->getQName() . " ?{$result->orderByVariable} .\n" );
		}

		return $result;

	}

	protected function buildPropertyCondition( SMWSomeProperty $description, $joinVariable, $orderByProperty ) {
		$diProperty = $description->getProperty();

		//*** Find out if we should order by the values of this property ***//
		$innerOrderByProperty = null; //TODO, also add to some global register if found below ...

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
		$propertyExpElement = SMWExporter::getResourceElement( $diProperty );
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

		//*** Possibly add conditions for ordering ***//
		if ( $orderByProperty !== null ) {
			$result->orderByVariable = $joinVariable . 'sk';
			$skeyExpElement = SMWExporter::getSpecialPropertyResource( '_SKEY' );
			$result->weakConditions = array( $result->orderByVariable => "?$joinVariable " . $skeyExpElement->getQName() . " ?{$result->orderByVariable} .\n" );
		}

		return $result;
	}

	protected function buildClassCondition( SMWClassDescription $description, $joinVariable, $orderByProperty ) {
		$condition = '';
		$namespaces = array();
		$instExpElement = SMWExporter::getSpecialPropertyResource( '_INST' );
		foreach( $description->getCategories() as $diWikiPage ) {
			$categoryExpElement = SMWExporter::getResourceElement( $diWikiPage );
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

		if ( $orderByProperty !== null ) {
			$result->orderByVariable = $joinVariable . 'sk';
			$skeyExpElement = SMWExporter::getSpecialPropertyResource( '_SKEY' );
			$result->weakConditions = array( $result->orderByVariable => "?$joinVariable " . $skeyExpElement->getQName() . " ?{$result->orderByVariable} .\n" );
		}

		return $result;
	}

	protected function buildValueCondition( SMWValueDescription $description, $joinVariable, $orderByProperty ) {
		$dataItem = $description->getDataItem();
		$expElement = SMWExporter::getDataItemExpElement( $dataItem );

		$comparator = '';
		switch ( $description->getComparator() ) {
			case SMW_CMP_EQ: $comparator = '='; break;
			case SMW_CMP_LESS: $comparator = '<'; break;
			case SMW_CMP_GRTR: $comparator = '>'; break;
			case SMW_CMP_LEQ: $comparator = '<='; break;
			case SMW_CMP_GEQ: $comparator = '>='; break;							
			case SMW_CMP_NEQ: $comparator = '!='; break;
// 			case SMW_CMP_LIKE: case SMW_CMP_NLKE:
// 				$comparator = ' LIKE ';
// 				if ( $description->getComparator() == SMW_CMP_NLKE ) $comparator = " NOT{$comparator}";
// 				$value =  str_replace( array( '%', '_', '*', '?' ), array( '\%', '\_', '%', '_' ), $value );
		}

		$namespaces = array();
		if ( $comparator == '=' ) {
			$result = new SMWSparqlSingletonCondition( $expElement );
		} else {
			$valueName = SMWTurtleSerializer::getTurtleNameForExpElement( $expElement );
			if ( $expElement instanceof SMWExpNsResource ) {
				$namespaces[$expElement->getNamespaceId()] = $expElement->getNamespace();
			}
			$filter = "?$joinVariable $comparator $valueName";
			$result = new SMWSparqlFilterCondition( $filter, $namespaces );
		}

		//*** Possibly add conditions for ordering ***//
		if ( $orderByProperty !== null ) {
			/// TODO Depends on datatype
// 			$result->orderByVariable = $joinVariable . 'sk';
// 			$skeyExpElement = SMWExporter::getSpecialPropertyResource( '_SKEY' );
// 			$result->weakConditions = array( $result->orderByVariable => "?$joinVariable " . $skeyExpElement->getQName() . " ?{$result->orderByVariable} .\n" );
		}

		return $result;
	}

	protected function getNextVariable() {
		return 'v' . ( ++$this->m_variableCounter );
	}

	/**
	 * Get a SPARQL option array for the given query.
	 *
	 * @param SMWQuery $query
	 * @return array
	 */
	protected function getSparqlOptions( SMWQuery $query ) {
		global $smwgQSortingSupport, $smwgQRandSortingSupport;

		$result = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );

		// Build ORDER BY options using discovered sorting fields.
// 		if ( $smwgQSortingSupport ) {
// 			$qobj = $this->m_queries[$rootid];
// 
// 			foreach ( $this->m_sortkeys as $propkey => $order ) {
// 				if ( ( $order != 'RANDOM' ) && array_key_exists( $propkey, $qobj->sortfields ) ) { // Field was successfully added.
// 					$result['ORDER BY'] = ( array_key_exists( 'ORDER BY', $result ) ? $result['ORDER BY'] . ', ' : '' ) . $qobj->sortfields[$propkey] . " $order ";
// 				} elseif ( ( $order == 'RANDOM' ) && $smwgQRandSortingSupport ) {
// 					$result['ORDER BY'] = ( array_key_exists( 'ORDER BY', $result ) ? $result['ORDER BY'] . ', ' : '' ) . ' RAND() ';
// 				}
// 			}
// 		}
		return $result;
	}

}