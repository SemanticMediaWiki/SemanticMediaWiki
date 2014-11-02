<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ValueConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ClassConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder\NamespaceConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder\PropertyConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConjunctionConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder\DisjunctionConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConceptConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ThingConditionBuilder;

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
	 * @var ConditionBuilder
	 */
	private static $defaultConditionBuilder = null;

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
				return $conditionBuilder->setCompoundConditionBuilder( $this->compoundConditionBuilder );
			}
		}

		return self::$defaultConditionBuilder->setCompoundConditionBuilder( $this->compoundConditionBuilder );
	}

	private function registerDefaultConditionBuilders() {
		$this->registerConditionBuilder( new ConjunctionConditionBuilder() );
		$this->registerConditionBuilder( new DisjunctionConditionBuilder() );
		$this->registerConditionBuilder( new PropertyConditionBuilder() );
		$this->registerConditionBuilder( new ValueConditionBuilder() );
		$this->registerConditionBuilder( new ClassConditionBuilder() );
		$this->registerConditionBuilder( new NamespaceConditionBuilder() );
		$this->registerConditionBuilder( new ConceptConditionBuilder() );

		self::$defaultConditionBuilder = new ThingConditionBuilder();
	}

}
