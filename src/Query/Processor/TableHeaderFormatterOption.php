<?php

namespace SMW\Query\Processor;

/**
 * The class supports formatter option to format table headers
 * Implements FormatterOptionsInterface for adding print requests
 * and handling parameters.
 *
 * @license GNU GPL v2+
 * @since 5.0.0
 *
 * @author milic
 */
class TableHeaderFormatterOption implements FormatterOptionsInterface {

	const FORMAT_LEGACY = 'format.legacy';
	const PRINT_THIS = 'print.this';

	public function addPrintRequest( $name, $param, $previousPrintout, $serialization ) {
		// Implementation omitted.
	}

	public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization ) {
		if ( $previousPrintout === null ) {
			return;
		}

		$param = substr( $param, 1 );
		$param = str_replace( 'thclass=', 'class', $param );

		if ( !empty( $param ) ) {
			$label = $serialization['printouts'][$previousPrintout]['label'] ?? '';

			// Use helper method to format label.
			$labelToSave = $this->formatLabel( $label, $param );

			// Save the label in serialization.
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

	/**
	 * Formats a label based on parameters and existing label content.
	 *
	 * @param string $label The existing label.
	 * @param string $param The parameter to append.
	 * @return string The formatted label.
	 */
	private function formatLabel( $label, $param ) {
		$partsLabel = explode( '=', $label );

		if ( isset( $partsLabel[1] ) && $partsLabel[1] === '' && !strpos( $partsLabel[0], '#' ) ) {
			return str_replace( '=', '', $label . ' ' . '#' . $param );
		} else {
			if ( strpos( $label, '#' ) ) {
				$parts = explode( '=', $label );
				if ( count( $parts ) > 1 ) {
					return $parts[0] . ';' . $param . '=' . $parts[1];
				} else {
					return str_replace( '=', '', $label . ';' . $param );
				}
			} else {
				$labelToSave = $label . ' ' . '#' . $param;
				$parts = explode( '=', $labelToSave );
				if ( count( $parts ) === 1 ) {
					return str_replace( '=', '', $labelToSave );
				} else {
					return $parts[0] . '' . $parts[2] . '=' . $parts[1];
				}
			}
		}
	}
}
