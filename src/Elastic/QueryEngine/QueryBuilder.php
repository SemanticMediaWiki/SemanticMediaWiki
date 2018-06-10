<?php

namespace SMW\Elastic\QueryEngine;

use Psr\Log\LoggerAwareTrait;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\DisjunctionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\SomePropertyInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter;
use SMW\Options;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Store;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class QueryBuilder {

	use LoggerAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var TermsLookup
	 */
	private $termsLookup;

	/**
	 * @var HierarchyLookup
	 */
	private $hierarchyLookup;

	/**
	 * @var FieldMapper
	 */
	private $fieldMapper;

	/**
	 * @var ConceptDescriptionInterpreter
	 */
	private $conceptDescriptionInterpreter;

	/**
	 * @var ClassDescriptionInterpreter
	 */
	private $classDescriptionInterpreter;

	/**
	 * @var ValueDescriptionInterpreter
	 */
	private $valueDescriptionInterpreter;

	/**
	 * @var SomePropertyInterpreter
	 */
	private $somePropertyInterpreter;

	/**
	 * @var ConjunctionInterpreter
	 */
	private $conjunctionInterpreter;

	/**
	 * @var DisjunctionInterpreter
	 */
	private $disjunctionInterpreter;

	/**
	 * @var NamespaceDescriptionInterpreter
	 */
	private $namespaceDescriptionInterpreter;

	/**
	 * @var array
	 */
	private $sortFields = [];

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @var array
	 */
	private $queryInfo = [];

	/**
	 * @var array
	 */
	private $descriptionLog = [];

	/**
	 * @var boolean
	 */
	protected $isConstantScore = true;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param TermsLookup $termsLookup
	 */
	public function __construct( Store $store, TermsLookup $termsLookup ) {
		$this->store = $store;
		$this->termsLookup = $termsLookup;

		$this->options = new Options();
		$this->hierarchyLookup = ApplicationFactory::getInstance()->newHierarchyLookup();
		$this->fieldMapper = new FieldMapper();
		$this->conceptDescriptionInterpreter = new ConceptDescriptionInterpreter( $this );
		$this->classDescriptionInterpreter = new ClassDescriptionInterpreter( $this );
		$this->valueDescriptionInterpreter = new ValueDescriptionInterpreter( $this );
		$this->somePropertyInterpreter = new SomePropertyInterpreter( $this );
		$this->conjunctionInterpreter = new ConjunctionInterpreter( $this );
		$this->disjunctionInterpreter = new DisjunctionInterpreter( $this );
		$this->namespaceDescriptionInterpreter = new NamespaceDescriptionInterpreter( $this );
	}

	/**
	 * @since 3.0
	 *
	 * @param Options $options
	 */
	public function setOptions( Options $options ) {
		$this->options = $options;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = false ) {
		return $this->options->safeGet( $key, $default );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $sortFields
	 */
	public function setSortFields( array $sortFields ) {
		$this->sortFields = $sortFields;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function isConstantScore() {
		return $this->isConstantScore;
	}

	/**
	 * @since 3.0
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * @since 3.0
	 *
	 * @return TermsLookup
	 */
	public function getTermsLookup() {
		return $this->termsLookup;
	}

	/**
	 * @since 3.0
	 *
	 * @return FieldMapper
	 */
	public function getFieldMapper() {
		return $this->fieldMapper;
	}

	/**
	 * @since 3.0
	 *
	 * @param []
	 */
	public function getQueryInfo() {
		return $this->queryInfo;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $queryInfo
	 */
	public function addQueryInfo( array $queryInfo ) {
		$this->queryInfo[] = $queryInfo;
	}

	/**
	 * @since 3.0
	 *
	 * @param []
	 */
	public function getDescriptionLog() {
		return $this->descriptionLog;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $error
	 */
	public function addError( array $error ) {
		$this->errors[] = $error;
	}

	/**
	 * @since 3.0
	 *
	 * @return integer
	 */
	public function getID( $dataItem ) {

		if ( $dataItem instanceof DIProperty ) {
			return (int)$this->store->getObjectIds()->getSMWPropertyID(
				$dataItem
			);
		}

		if ( $dataItem instanceof DIWikiPage ) {
			return (int)$this->store->getObjectIds()->getSMWPageID(
				$dataItem->getDBKey(),
				$dataItem->getNamespace(),
				$dataItem->getInterWiki(),
				$dataItem->getSubobjectName()
			);
		}

		return 0;
	}

	/**
	 * @since 3.0
	 *
	 * @param Condition|array $params
	 *
	 * @return Condition
	 */
	public function newCondition( $params ) {
		return new Condition( $params );
	}

	/**
	 * @since 3.0
	 *
	 * @param Description $description
	 * @param boolean $isConstantScore
	 *
	 * @return array
	 */
	public function makeFromDescription( Description $description, $isConstantScore = true ) {

		$this->errors = [];
		$this->queryInfo = [];

		$this->descriptionLog = [];
		$this->isConstantScore = $isConstantScore;
		$this->termsLookup->clear();

		$this->fieldMapper->isCompatMode(
			$this->options->safeGet( 'compat.mode', true )
		);

		// Some notes on the difference between term, match, and query
		// string:
		//
		// - match, term or range queries: look for a particular value in a
		//   particular field
		// - bool: wrap other leaf or compound queries and are used to combine
		//   multiple queries
		// - constant_score: simply returns a constant score equal to the query
		//   boost for every document in the filter

		$condition = $this->interpretDescription( $description );

		if ( $condition instanceof Condition ) {
			$query = $condition->toArray();
			$this->descriptionLog = $condition->getLogs();
		} else {
			$query = $condition;
		}

		if ( $this->options->safeGet( 'sort.property.must.exists' ) && $this->sortFields !== [] ) {
			$params = [];

			foreach ( $this->sortFields as $field ) {
				$params[] = $this->fieldMapper->exists( "$field" );
			}

			$query = $this->fieldMapper->bool( 'must', [ $query, $params ] );
		}

		// If we know we don't need any score we turn this into a `constant_score`
		// query
		// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-constant-score-query.html
		if ( $this->isConstantScore ) {
			$query = $this->fieldMapper->constant_score( $query );
		}

		return $query;
	}

	/**
	 * @since 3.0
	 *
	 * @param DataItem|null $dataItem
	 * @param integer $hierarchyDepth
	 *
	 * @return array
	 */
	public function findHierarchyMembers( DataItem $dataItem = null, $hierarchyDepth ) {

		$ids = [];

		if ( $dataItem !== null && ( $members = $this->hierarchyLookup->getConsecutiveHierarchyList( $dataItem ) ) !== [] ) {

			if ( $hierarchyDepth !== null ) {
				$members = $hierarchyDepth == 0 ? [] : array_slice( $members, 0, $hierarchyDepth );
			}

			foreach ( $members as $member ) {
				$ids[] = $this->getID( $member );
			}
		}

		return $ids;
	}

	/**
	 * @since 3.0
	 *
	 * @param Description $description
	 *
	 * @return array
	 */
	public function interpretDescription( Description $description, $isConjunction = false ) {

		$params = [];

		if ( $description instanceof SomeProperty ) {
			$params = $this->somePropertyInterpreter->interpretDescription( $description, $isConjunction );
		}

		if ( $description instanceof ConceptDescription ) {
			$params = $this->conceptDescriptionInterpreter->interpretDescription( $description, $isConjunction );
		}

		if ( $description instanceof ClassDescription ) {
			$params = $this->classDescriptionInterpreter->interpretDescription( $description, $isConjunction );
		}

		if ( $description instanceof NamespaceDescription ) {
			$params = $this->namespaceDescriptionInterpreter->interpretDescription( $description, $isConjunction );
		}

		if ( $description instanceof ValueDescription ) {
			$params = $this->valueDescriptionInterpreter->interpretDescription( $description, $isConjunction );
		}

		if ( $description instanceof Conjunction ) {
			$params = $this->conjunctionInterpreter->interpretDescription( $description, $isConjunction );
		}

		if ( $description instanceof Disjunction ) {
			$params = $this->disjunctionInterpreter->interpretDescription( $description, $isConjunction );
		}

		return $params;
	}

}
