<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\ApplicationFactory;
use SMW\Store;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\DisjunctionConjunctionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\DispatchingDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\SomePropertyInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ThingDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter;
use SMW\Utils\CircularReferenceGuard;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DescriptionInterpreterFactory {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var CircularReferenceGuard
	 */
	private $circularReferenceGuard;

	/**
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 */
	public function __construct( Store $store, CircularReferenceGuard $circularReferenceGuard ) {
		$this->store = $store;
		$this->circularReferenceGuard = $circularReferenceGuard;
	}

	/**
	 * @since 2.4
	 *
	 * @param ConditionBuilder $conditionBuilder
	 *
	 * @return DispatchingDescriptionInterpreter
	 */
	public function newDispatchingDescriptionInterpreter( ConditionBuilder $conditionBuilder ) {

		$pplicationFactory = ApplicationFactory::getInstance();
		$dispatchingDescriptionInterpreter = new DispatchingDescriptionInterpreter();

		$fulltextSearchTableFactory = new FulltextSearchTableFactory();

		$valueMatchConditionBuilder = $fulltextSearchTableFactory->newValueMatchConditionBuilderByType(
			$this->store
		);

		$dispatchingDescriptionInterpreter->addDefaultInterpreter(
			new ThingDescriptionInterpreter( $conditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new SomePropertyInterpreter( $this->store, $conditionBuilder, $valueMatchConditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new DisjunctionConjunctionInterpreter( $conditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new NamespaceDescriptionInterpreter( $this->store, $conditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ClassDescriptionInterpreter( $this->store, $conditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ValueDescriptionInterpreter( $this->store, $conditionBuilder )
		);

		$conceptDescriptionInterpreter = new ConceptDescriptionInterpreter(
			$this->store,
			$conditionBuilder,
			$this->circularReferenceGuard
		);

		$conceptDescriptionInterpreter->setQueryParser(
			$pplicationFactory->getQueryFactory()->newQueryParser(
				$pplicationFactory->getSettings()->get( 'smwgQConceptFeatures' )
			)
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			$conceptDescriptionInterpreter
		);

		return $dispatchingDescriptionInterpreter;
	}

}
