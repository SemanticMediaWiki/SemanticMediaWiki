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

	public function addPrintRequest( $name, $param, $previousPrintout, $serialization ) {
	}

	public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization ) {
		if ( $previousPrintout === null ) {
			return;
		}

		$param = substr( $param, 1 );
		$param = str_replace( 'thclass=', 'class', $param );
		$parts = [];

		if ( !empty( $param ) ) {
			// check the previous label, remove and split it by '='
			$label = $serialization['printouts'][$previousPrintout]['label'];
			$partsLabel = explode( '=', $label );

			if ( isset( $partsLabel[1] ) && $partsLabel[1] === '' && !strpos( $partsLabel[0], '#' ) ) {
				$labelToSave = $label . ' ' . '#' . $param;
				$labelToSave = str_replace( '=', '', $labelToSave );
			} else {
				if ( strpos( $label, '#' ) ) {
					$parts = explode( '=', $label );
					if ( count( $parts ) > 1 ) {
						$labelToSave = $parts[0] . ';' . $param . '=' . $parts[1];
					} else {
						$labelToSave = $label . ';' . $param;
						$labelToSave = str_replace( '=', '', $labelToSave );
					}
				} else {
					$labelToSave = $label . ' ' . '#' . $param;
					$parts = explode( '=', $labelToSave );
					if ( count( $parts ) === 1 ) {
						$labelToSave = str_replace( '=', '', $labelToSave );
					} else {
						$labelToSave = $parts[0] . '' . $parts[2] . '=' . $parts[1];
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
