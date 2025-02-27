<?php

namespace SMW\Query\Processor;

/**
 * Interface for custom presentation formatting in ASK queries.
 *
 * This interface is intended for custom formatters like `SizeFormatter`, `LinkFormatter`,
 * and `TableHeaderFormatter`, which are responsible for processing specific query parameters
 * and formatting them for presentation. These formatters implement the interface and provide
 * logic for how each parameter should be displayed.
 *
 * @license GNU GPL v2+
 * @since 5.0.0
 *
 * @author milic
 */
interface PresentationLayerInterface {

	/**
	 * Processes the given parameter and returns a formatted print request with output markers.
	 *
	 * This method is expected to handle the specific formatting for the provided parameter (`$param`)
	 * and prepare it for display by the `QueryResults`. It should include any necessary markers or
	 * transformations required for output, as well as update the `$serialization` if needed.
	 *
	 * @param string $name The name of the label or parameter being processed.
	 * @param string $param The value or parameter that needs to be formatted.
	 * @param string $previousPrintout The previous printout from which the new output will be based.
	 * @param array $serialization The serialized data structure used to store the state and formatting.
	 *
	 * @return array An array containing the processed print request and the updated serialization.
	 *
	 * @since 5.0.0
	 */
	public function getPrintRequestWithOutputMarker( $name, $param, $previousPrintout, $serialization );
}
