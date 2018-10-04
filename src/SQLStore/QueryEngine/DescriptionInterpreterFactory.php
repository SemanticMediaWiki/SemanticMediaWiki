<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\ApplicationFactory;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\DisjunctionConjunctionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\DispatchingDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\SomePropertyInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ThingDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter;

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

		$pplicationFactory = ApplicationFactory::getInstance();
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

		$conceptDescriptionInterpreter = new ConceptDescriptionInterpreter(
			$querySegmentListBuilder
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
