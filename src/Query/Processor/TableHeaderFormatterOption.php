<?php

namespace SMW\Query\Processor;

/**
 * The class supports formatter option to format table headers
 * The class implements FormatterOptionsInterface which holds the 
 * functions for adding print requests and handling parameters
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author milic
 */
class TableHeaderFormatterOption implements FormatterOptionsInterface {

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
		$param = str_replace( 'thclass=', 'class', $param );
		
		if ( isset( $param ) ) {
			$label = $serialization['printouts'][$previousPrintout]['label'];
			$label = preg_replace( '/=$/', '', $label );
			$labelParts = explode( '=', $label );

			if ( strpos( $label,'#' ) ) {
				$labelToSave = $label . ';' . $param;
				$labelToSave = str_replace( '=', '', $labelToSave );
			} else {
				if (count( $labelParts ) > 1 ) {
					$labelToSave = $label . ' ' . '#' . $param;
					if ( str_starts_with( $labelToSave, '=' ) ) {
						$labelToSave = substr( $labelToSave, 1 );
					}
				} else {
					$labelToSave = $label . ' ' . '#' . $param;
					$labelToSave = str_replace( '=', '', $labelToSave );
				}	
			}

			if ( str_contains( $labelToSave, '=' ) ) {
				// rebuild the final label before save to be in use for Deserializer getPartsFromText()
				list( $key, $value ) = explode( '=', $labelToSave, 2 );

				if ( $key != '' ) {
					// Check if the value contains a '#'
					if ( strpos( $value, '#' ) !== false ) {
						// Split the value by '#'
						list( $mainValue, $class ) = explode( '#', $value, 2 );
						
						// Rebuild the string with the class appended to the key
						$labelToSave = trim( $key . ' #' . $class . '=' . trim( $mainValue ) );
					} else {
						// If there's no '#', just use the original format
						$labelToSave = trim( $labelToSave );
					}
				}
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
