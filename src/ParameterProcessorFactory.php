<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ParameterProcessorFactory {

	/**
	 * @since 1.9
	 *
	 * @param array $parameters
	 *
	 * @return ParserParameterProcessor
	 */
	public static function newFromArray( array $parameters ) {
		$instance = new self();
		return $instance->newParserParameterProcessor( $parameters );
	}

	/**
	 * @since 2.3
	 *
	 * @param array $parameters
	 *
	 * @return ParserParameterProcessor
	 */
	public function newParserParameterProcessor( array $parameters ) {

		if ( isset( $parameters[0] ) && is_object( $parameters[0] ) ) {
			array_shift( $parameters );
		}

		return new ParserParameterProcessor( $parameters );
	}

}
