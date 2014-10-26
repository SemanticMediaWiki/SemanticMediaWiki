<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ValueConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ClassConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder\NamespaceConditionBuilder;

use SMW\Query\Language\Description;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConditionBuilderStrategyFinder {

	/**
	 * @var CompoundConditionBuilder
	 */
	private $compoundConditionBuilder;

	/**
	 * By design this property is kept static, benchmark tests showed an improved
	 * performance
	 *
	 * @var ConditionBuilder[]
	 */
	private static $conditionBuilders = array();

	/**
	 * @since 2.1
	 *
	 * @param CompoundConditionBuilder $compoundConditionBuilder
	 */
	public function __construct( CompoundConditionBuilder $compoundConditionBuilder ) {
		$this->compoundConditionBuilder = $compoundConditionBuilder;
	}

	/**
	 * @since  2.1
	 */
	public function clear() {
		self::$conditionBuilders = array();
	}

	/**
	 * @since  2.1
	 *
	 * @param ConditionBuilder $conditionBuilder
	 */
	public function registerConditionBuilder( ConditionBuilder $conditionBuilder ) {
		self::$conditionBuilders[] = $conditionBuilder;
	}

	/**
	 * @since  2.1
	 *
	 * @param Description $description
	 *
	 * @return ConditionBuilder
	 */
	public function findStrategyForDescription( Description $description ) {

		if ( self::$conditionBuilders === array() ) {
			$this->registerDefaultConditionBuilders();
		}

		foreach ( self::$conditionBuilders as $conditionBuilder ) {
			if ( $conditionBuilder->canBuildConditionFor( $description ) ) {
				return $conditionBuilder;
			}
		}

		return null;
	}

	private function registerDefaultConditionBuilders() {
		$this->registerConditionBuilder( new ValueConditionBuilder( $this->compoundConditionBuilder ) );
		$this->registerConditionBuilder( new ClassConditionBuilder( $this->compoundConditionBuilder ) );
		$this->registerConditionBuilder( new NamespaceConditionBuilder( $this->compoundConditionBuilder ) );
	}

}
