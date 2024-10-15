<?php

namespace SMW\Query\Processor;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author milic
 */
class LinkValueFormatter implements FormatterOptionsInterface {

    /**
	 * Format type
	 */
	const FORMAT_LEGACY = 'format.legacy';

	/**
	 * Identify the PrintThis instance
	 */
	const PRINT_THIS = 'print.this';


    public function addPrintRequest( $name, $param, $previousPrintout, $serialization ) {}

	 /**
     * Add a print request to the image formatter options.
     *
     * @param string $name The name of the print request.
     * @param mixed $param Additional parameters for the request.
     * @param mixed $previousPrintout Data from previous printouts.
     * @param mixed $serialization Information for serialization.
     *
     * @return array The updated serialization array.
     * @throws InvalidArgumentException if arguments are invalid.
     */
    public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization ) {
        if ( $previousPrintout === null ) {
			return;
		}

		$param = substr( $param, 1 );
		
		if ( true ) {
			$label = $serialization['printouts'][$previousPrintout]['label'];

			if (strpos($label,'#')) {
				$labelToSave = $label . ';' . $param;
			} else {
				$labelToSave = $label . ' ' . '#' . $param;
				$labelToSave = str_replace( '=', '', $labelToSave );
			}

			$serialization['printouts'][$previousPrintout] = [
				'label' => $labelToSave,
				'params' => []
			];

		} else {
			$serialization['printouts'][$previousPrintout]['params'] = null;
		}

		return [
			'serialization' => $serialization,
			'previousPrintout' => $previousPrintout,
		];
    }
}
