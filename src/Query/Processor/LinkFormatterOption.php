<?php

namespace SMW\Query\Processor;

/**
 * The class supports formatter option in ask queries
 * to set links, e.g., ?Main Image=|+link=
 * Implements FormatterOptionsInterface for adding print requests
 * and handling parameters.
 *
 * @license GNU GPL v2+
 * @since 5.0.0
 *
 * @author milic
 */
class LinkFormatterOption implements FormatterOptionsInterface {

	const FORMAT_LEGACY = 'format.legacy';
	const PRINT_THIS = 'print.this';

	public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization ) {
		if ( $previousPrintout === null ) {
			return;
		}

		$param = substr( $param, 1 );

		if ( !empty( $param ) ) {
			$label = $serialization['printouts'][$previousPrintout]['label'] ?? '';

			// Use helper method to format label.
			$labelToSave = $this->formatLabel( $label, $param );

			// Save the label in serialization.
			$serialization['printouts'][$previousPrintout] = [
				'label' => $labelToSave,
				'params' => [],
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
		if ( str_contains( $label, '#' ) ) {
			if ( str_contains( $label, '=' ) ) {
				$parts = explode( '=', $label );
				return $parts[0] . ';' . $param . $parts[1];
			}
			return str_replace( '=', '', $label . ';' . $param );
		}

		if ( str_contains( $label, '=' ) ) {
			$parts = explode( '=', $label );
			return $parts[0] !== ''
				? $parts[0] . '#' . $param . $parts[1]
				: str_replace( '=', '', $label . ' #' . $param );
		}

		return str_replace( '=', '', $label . ' #' . $param );
	}
}
