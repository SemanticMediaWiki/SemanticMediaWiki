<?php

namespace SMW;

/**
 * ParserData dependency object specification
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * ParserData dependency object specification
 *
 * @ingroup DependencyContainer
 * @ingroup DependencyObject
 */
class DiParserData implements DependencyObject {

	/**
	 * @see DependencyObject::defineObject
	 *
	 * @since  1.9
	 *
	 * @param DependencyBuilder $builder
	 */
	public function defineObject( DependencyBuilder $builder ) {

		$parserData = new ParserData(
			$builder->getArgument( 'Title' ),
			$builder->getArgument( 'ParserOutput' )
		);

		$parserData->setObservableDispatcher( $builder->newObject( 'ObservableUpdateDispatcher' ) );

		return $parserData;

	}

}
