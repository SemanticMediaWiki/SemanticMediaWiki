<?php

namespace SMW\SQLStore\QueryEngine;

/**
 * Class for representing a single (sub)query description.
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class QuerySegment {

	/**
	 * Type of empty query without usable condition, dropped as soon as
	 * discovered. This is used only during preparing the query (no
	 * queries of this type should ever be added).
	 */
	const Q_NOQUERY = 0;

	/**
	 * Type of query that is a join with a query (jointable: internal
	 * table name; joinfield/components/where use alias.fields;
	 * from uses external table names, components interpreted
	 * conjunctively (JOIN)).
	 */
	const Q_TABLE = 1;

	/**
	 * Type of query that matches a constant value (joinfield is a
	 * disjunctive array of unquoted values, jointable empty, components
	 * empty).
	 */
	const Q_VALUE = 2;

	/**
	 * Type of query that is a disjunction of other queries
	 * (joinfield/jointable empty; only components relevant)
	 */
	const Q_DISJUNCTION = 3;

	/**
	 * Type of query that is a conjunction of other queries
	 * (joinfield/jointable empty; only components relevant).
	 */
	const Q_CONJUNCTION = 4;

	/**
	 * Type of query that creates a temporary table of all superclasses
	 * of given classes (only joinfield relevant: (disjunctive) array of
	 * unquoted values).
	 */
	const Q_CLASS_HIERARCHY = 5;

	/**
	 * Type of query that creates a temporary table of all superproperties
	 * of given properties (only joinfield relevant: (disjunctive) array
	 * of unquoted values).
	 */
	const Q_PROP_HIERARCHY = 6;

	/**
	 * @var integer
	 */
	public $type = self::Q_TABLE;

	/**
	 * @var integer|null
	 */
	public $depth;

	/**
	 * @var string
	 */
	public $fingerprint = '';

	/**
	 * @var boolean
	 */
	public $null = false;

	/**
	 * @var boolean
	 */
	public $not = false;

	/**
	 * @var string
	 * @note This should be only one of these values: 'LEFT', 'LEFT OUTER', 'INNER'.
	 */
	public $joinType = '';

	/**
	 * @var string
	 */
	public $joinTable = '';

	/**
	 * @var string|array
	 */
	public $joinfield = '';

	/**
	 * Allows to define an index field, for example in case when a sub-query rewires
	 * a match condition.
	 *
	 * @var string
	 */
	public $indexField = '';

	/**
	 * @var string
	 */
	public $from = '';

	/**
	 * @var string[] Array of tables compatible with MediaWiki’s IReadableDatabase::select()
	 *
	 * The values are always the table names, and if the index is a string then it is its alias.
	 * It is not necessary to use $db->tableName() for the table names, this is handled by MediaWiki.
	 *
	 * Example:
	 *  [ 'page', 't0' => 'smw_object_ids' ]
	 */
	public $fromTables = [];

	/**
	 * @var string[][] Array of JOIN conditions created to be compatible with MediaWiki’s IReadableDatabase::select()
	 * 
	 * The key in the first array must be a string, and represent the table or alias; the corresponding values
	 * are a list with index 0 and 1, where the value at index 0 is the the type of JOIN and the value at index 1
	 * is the condition.
	 * It is not necessary to use $db->tableName() for the table names, this is handled by MediaWiki.
	 *
	 *
	 * Example:
	 *   [ 'page' => [ 'LEFT JOIN', 'page_latest=rev_id' ] ]
	 */
	public $joinConditions = [];

	/**
	 * @var string
	 */
	public $where = '';

	/**
	 * @var string
	 */
	public $sortIndexField;

	/**
	 * @var string[]
	 */
	public $components = [];

	/**
	 * The alias to be used for jointable; read-only after construct!
	 *
	 * @var string
	 */
	public $alias;

	/**
	 * property dbkey => db field; passed down during query execution.
	 *
	 * @var string[]
	 */
	public $sortfields = [];

	/**
	 * @var integer
	 */
	public $queryNumber;

	/**
	 * @var integer
	 */
	public static $qnum = 0;

	/**
	 * @since 2.2
	 */
	public function __construct() {
		$this->queryNumber = self::$qnum;
		$this->alias = 't' . self::$qnum;
		self::$qnum++;
	}

	/**
	 * @since 2.2
	 */
	public function reset() {
		self::$qnum = 0;

		$this->queryNumber = self::$qnum;
		$this->alias = 't' . self::$qnum;
		self::$qnum++;
	}

}
