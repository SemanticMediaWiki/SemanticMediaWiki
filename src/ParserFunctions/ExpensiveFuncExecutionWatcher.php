<?php

namespace SMW\ParserFunctions;

use SMW\ParserData;
use SMWQuery as Query;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ExpensiveFuncExecutionWatcher {

	/**
	 * Idenitifer
	 */
	const EXPENSIVE_COUNTER = 'smw-expensiveparsercount';

	/**
	 * @var ParserData
	 */
	private $parserData;

	/**
	 * @var integer
	 */
	private $expensiveThreshold = 10;

	/**
	 * @var integer|boolean
	 */
	private $expensiveExecutionLimit = false;

	/**
	 * @since 3.0
	 *
	 * @param ParserData $parserData
	 */
	public function __construct( ParserData $parserData ) {
		$this->parserData = $parserData;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $expensiveThreshold
	 */
	public function setExpensiveThreshold( $expensiveThreshold ) {
		$this->expensiveThreshold = $expensiveThreshold;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer|boolean $expensiveExecutionLimit
	 */
	public function setExpensiveExecutionLimit( $expensiveExecutionLimit ) {
		$this->expensiveExecutionLimit = $expensiveExecutionLimit;
	}

	/**
	 * @since 3.0
	 *
	 * @param Query $query
	 *
	 * @return boolean
	 */
	public function hasReachedExpensiveLimit( Query $query ) {

		if ( $this->expensiveExecutionLimit === false ) {
			return false;
		}

		if ( $query->getLimit() == 0 ) {
			return false;
		}

		if ( $this->parserData->getOutput()->getExtensionData( self::EXPENSIVE_COUNTER ) < $this->expensiveExecutionLimit ) {
			return false;
		}

		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @param Query $query
	 *
	 * @return boolean
	 */
	public function incrementExpensiveCount( Query $query ) {

		if ( $this->expensiveExecutionLimit === false || $query->getLimit() == 0 || $query->getOption( Query::PROC_QUERY_TIME ) < $this->expensiveThreshold  ) {
			return;
		}

		$output = $this->parserData->getOutput();
		$expensiveCount = $output->getExtensionData( self::EXPENSIVE_COUNTER );

		if ( !is_int( $expensiveCount ) ) {
			$expensiveCount = 0;
		}

		$expensiveCount++;
		$output->setExtensionData( self::EXPENSIVE_COUNTER, $expensiveCount );
	}

}
