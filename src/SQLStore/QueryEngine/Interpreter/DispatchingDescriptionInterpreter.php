<?php

namespace SMW\SQLStore\QueryEngine\Interpreter;

use SMW\Query\Language\Description;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DispatchingDescriptionInterpreter implements DescriptionInterpreter {

	/**
	 * @var DescriptionInterpreter[]
	 */
	private $interpreters = array();

	/**
	 * @var DescriptionInterpreter
	 */
	private $defaultInterpreter = null;

	/**
	 * @param Description $description
	 *
	 * @return boolean
	 */
	public function canInterpretDescription( Description $description ) {

		foreach ( $this->interpreters as $interpreter ) {
			if ( $interpreter->canInterpretDescription( $description ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Description $description
	 *
	 * @return QuerySegment
	 * @throws InvalidArgumentException
	 */
	public function interpretDescription( Description $description ) {

		foreach ( $this->interpreters as $interpreter ) {
			if ( $interpreter->canInterpretDescription( $description ) ) {
				return $interpreter->interpretDescription( $description );
			}
		}

		// Instead of throwing an exception we return a ThingDescriptionInterpreter
		// for all unregistered/unknown descriptions
		return $this->defaultInterpreter->interpretDescription( $description );
	}

	/**
	 * @since  2.2
	 *
	 * @param DescriptionInterpreter $defaultInterpreter
	 */
	public function addInterpreter( DescriptionInterpreter $interpreter ) {
		$this->interpreters[] = $interpreter;
	}

	/**
	 * @since 2.2
	 *
	 * @param DescriptionInterpreter $defaultInterpreter
	 */
	public function addDefaultInterpreter( DescriptionInterpreter $defaultInterpreter ) {
		$this->defaultInterpreter = $defaultInterpreter;
	}

}
