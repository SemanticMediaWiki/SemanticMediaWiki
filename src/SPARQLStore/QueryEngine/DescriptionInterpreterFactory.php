<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\DisjunctionInterpreter;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\DispatchingDescriptionInterpreter;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\SomePropertyInterpreter;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ThingDescriptionInterpreter;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DescriptionInterpreterFactory {

	/**
	 * @since 2.5
	 *
	 * @param CompoundConditionBuilder $compoundConditionBuilder
	 *
	 * @return DispatchingDescriptionInterpreter
	 */
	public function newDispatchingDescriptionInterpreter( CompoundConditionBuilder $compoundConditionBuilder ) {

		$dispatchingDescriptionInterpreter = new DispatchingDescriptionInterpreter();

		$dispatchingDescriptionInterpreter->addDefaultInterpreter(
			new ThingDescriptionInterpreter( $compoundConditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new SomePropertyInterpreter( $compoundConditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ConjunctionInterpreter( $compoundConditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new DisjunctionInterpreter( $compoundConditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new NamespaceDescriptionInterpreter( $compoundConditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ClassDescriptionInterpreter( $compoundConditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ValueDescriptionInterpreter( $compoundConditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ConceptDescriptionInterpreter( $compoundConditionBuilder )
		);

		return $dispatchingDescriptionInterpreter;
	}

}
