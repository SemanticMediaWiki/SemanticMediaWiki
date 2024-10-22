<?php

namespace SMW\Query\Processor;

/**
 * The class supports formatter option in ask queries 
 * to set link, example ?Main Image=|+link=
 * The class implements FormatterOptionsInterface which holds the 
 * functions for adding print requests and handling parameters
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author milic
 */
class LinkFormatterOption implements FormatterOptionsInterface {

    /**
	 * Format type
	 */
	const FORMAT_LEGACY = 'format.legacy';

	/**
	 * Identify the PrintThis instance
	 */
	const PRINT_THIS = 'print.this';


    public function addPrintRequest( $name, $param, $previousPrintout, $serialization ) {}

    public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization ) {
        if ( $previousPrintout === null ) {
			return;
		}

		$param = substr( $param, 1 );
		
		if ( isset( $param ) ) {
			$label = $serialization['printouts'][$previousPrintout]['label'];

			if (strpos($label,'#')) {
				$labelToSave = $label . ';' . $param;
				$labelToSave = str_replace( '=', '', $labelToSave );
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