<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\Interpreter\ClassDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\ConceptDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\DisjunctionConjunctionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\DispatchingDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\NamespaceDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\SomePropertyInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\ThingDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\ValueDescriptionInterpreter;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DescriptionInterpreterFactory {

	/**
	 * @since 2.4
	 *
	 * @param QuerySegmentListBuilder $querySegmentListBuilder
	 *
	 * @return DispatchingDescriptionInterpreter
	 */
	public function newDispatchingDescriptionInterpreter( QuerySegmentListBuilder $querySegmentListBuilder ) {

		$dispatchingDescriptionInterpreter = new DispatchingDescriptionInterpreter();

		$dispatchingDescriptionInterpreter->addDefaultInterpreter(
			new ThingDescriptionInterpreter( $querySegmentListBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new SomePropertyInterpreter( $querySegmentListBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new DisjunctionConjunctionInterpreter( $querySegmentListBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new NamespaceDescriptionInterpreter( $querySegmentListBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ClassDescriptionInterpreter( $querySegmentListBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ValueDescriptionInterpreter( $querySegmentListBuilder )
		);

		$dispatchingDescriptionInterpreter->addInterpreter(
			new ConceptDescriptionInterpreter( $querySegmentListBuilder )
		);

		return $dispatchingDescriptionInterpreter;
	}

}
