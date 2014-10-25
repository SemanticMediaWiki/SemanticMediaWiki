<?php

namespace SMW\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\Query\Language\Description;
use SMW\SPARQLStore\QueryEngine\Condition\Condition;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
interface ConditionBuilder {

	/**
	 * @since 2.1
	 *
	 * @param Description $description
	 *
	 * @return boolean
	 */
	public function canBuildConditionFor( Description $description );

	/**
	 * @since 2.1
	 *
	 * @param Description $description
	 * @param $joinVariable
	 * @param $orderByProperty
	 *
	 * @return Condition
	 */
	public function buildCondition( Description $description, $joinVariable, $orderByProperty );

}