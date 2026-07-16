<?php

namespace SMW;

/**
 * @license GPL-2.0-or-later
 * @since   1.9
 *
 * @author mwjames
 */
class ParameterProcessorFactory {

	/**
	 * @since 1.9
	 *
	 * @param array $parameters
	 * @param bool $captureDisplayOptions
	 *
	 * @return ParserParameterProcessor
	 */
	public static function newFromArray( array $parameters, bool $captureDisplayOptions = false ): ParserParameterProcessor {
		$instance = new self();
		return $instance->newParserParameterProcessor( $parameters, $captureDisplayOptions );
	}

	/**
	 * @since 2.3
	 *
	 * @param array $parameters
	 * @param bool $captureDisplayOptions
	 *
	 * @return ParserParameterProcessor
	 */
	public function newParserParameterProcessor( array $parameters, bool $captureDisplayOptions = false ): ParserParameterProcessor {
		if ( isset( $parameters[0] ) && is_object( $parameters[0] ) ) {
			array_shift( $parameters );
		}

		return new ParserParameterProcessor( $parameters, $captureDisplayOptions );
	}

}
