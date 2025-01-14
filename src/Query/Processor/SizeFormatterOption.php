<?php

namespace SMW\Query\Processor;

/**
 * The class supports size formatter option in ask queries
 * example ?Main Image=|width=+30px or ?Main Image=|height=+30px
 * Implements FormatterOptionsInterface for adding print requests
 * and handling parameters.
 *
 *
 * @license GNU GPL v2+
 * @since 5.0.0
 *
 * @author milic
 */
class SizeFormatterOption implements FormatterOptionsInterface {

	const FORMAT_LEGACY = 'format.legacy';
	const PRINT_THIS = 'print.this';

	public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization ) {
		if ( $previousPrintout === null ) {
			return;
		}

		$param = substr( $param, 1 );
		if ( empty( $param ) ) {
			return;
		}

		$parts = explode( '=', $param, 2 );
		$label = $serialization['printouts'][$previousPrintout]['label'] ?? '';

		$labelToSave = $this->formatLabel( $label, $param );

		$serialization['printouts'][$previousPrintout] = [
			'label' => $labelToSave,
			'params' => []
		];

		$serialization['printouts'][$previousPrintout]['params'][trim( $parts[0] )] = $parts[1] ?? null;

		return [
			'serialization' => $serialization,
			'previousPrintout' => $previousPrintout,
		];
	}

	private function formatLabel( $label, $param ) {
		if ( strpos( $label, '#' ) !== false ) {
			if ( str_contains( $param, 'width=' ) ) {
				$adjustedWidth = rtrim( explode( '=', $param )[1], 'px' );
				$parts = explode( '#', $label );
				return $parts[0] . '#' . $adjustedWidth . $parts[1];
			}

			if ( str_contains( $param, 'height=' ) ) {
				$adjustedHeight = explode( '=', $param )[1];
				$adjustedWidth = rtrim( $label, 'px' );
				return $adjustedWidth . 'x' . $adjustedHeight;
			}

			return $label . ';' . $param;
		}

		$labelToSave = $label . ' #' . $param;
		$labelToSave = str_replace( [ 'width=', 'height=' ], [ '', 'x' ], $labelToSave );
		return str_replace( '=', '', $labelToSave );
	}
}
