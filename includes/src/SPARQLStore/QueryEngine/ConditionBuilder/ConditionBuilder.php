<?php

namespace SMW\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\Query\Language\Description;
use SMW\SPARQLStore\QueryEngine\Condition\Condition;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;

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
	 * @param CompoundConditionBuilder $compoundConditionBuilder
	 */
	public function setCompoundConditionBuilder( CompoundConditionBuilder $compoundConditionBuilder );

	/**
	 * @since 2.1
	 *
	 * @param ClassDescription $description
	 * @param string $joinVariable
	 * @param DIProperty|null $orderByProperty
	 *
	 * @return Condition
	 */
	public function buildCondition( Description $description, $joinVariable, $orderByProperty = null );

}