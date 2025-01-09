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

	public function addPrintRequest( $name, $param, $previousPrintout, $serialization ) {
	}

	public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization ) {
		if ( $previousPrintout === null ) {
			return;
		}

		$param = substr( $param, 1 );
		// $labelToSave = '';

		if ( !empty( $param ) ) {
			// fetch the previous label
			$label = $serialization['printouts'][$previousPrintout]['label'];
			$labelToSave = '';
			// check the label and create final label with correct format
			if ( str_contains( $label, '#' ) ) {
				if ( str_contains( $label, '=' ) ) {
					$parts = explode( '=', $label );
					$labelToSave = $parts[ 0 ] . ';' . $param . '' . $parts[ 1 ];
				} else {
					$labelToSave = $label . ';' . $param;
					$labelToSave = str_replace( '=', '', $labelToSave );
				}
			} else {
				if ( str_contains( $label, '=' ) ) {
					$parts = explode( '=', $label );
					if ( $parts[0] != '' ) {
						$labelToSave = $parts[ 0 ] . '#' . $param . '' . $parts[ 1 ];
					} else {
						$labelToSave = $label . ' ' . '#' . $param;
						$labelToSave = str_replace( '=', '', $labelToSave );
					}
				} else {
					$labelToSave = $label . ' ' . '#' . $param;
					$labelToSave = str_replace( '=', '', $labelToSave );
				}
			}

			// save the label as a part of serialization
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
