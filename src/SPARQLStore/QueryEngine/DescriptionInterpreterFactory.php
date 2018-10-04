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
	 * @param ConditionBuilder $conditionBuilder
	 *
	 * @return DispatchingDescriptionInterpreter
	 */
	public function newDispatchingDescriptionInterpreter( ConditionBuilder $conditionBuilder ) {

		$dispatchingDescriptionInterpreter = new DispatchingDescriptionInterpreter();

		$dispatchingDescriptionInterpreter->addDefaultInterpreter(
			new ThingDescriptionInterpreter( $conditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new SomePropertyInterpreter( $conditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ConjunctionInterpreter( $conditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new DisjunctionInterpreter( $conditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new NamespaceDescriptionInterpreter( $conditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ClassDescriptionInterpreter( $conditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ValueDescriptionInterpreter( $conditionBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ConceptDescriptionInterpreter( $conditionBuilder )
		);

		return $dispatchingDescriptionInterpreter;
	}

}
