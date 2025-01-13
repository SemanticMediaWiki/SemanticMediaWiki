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

	public function addPrintRequest( $name, $param, $previousPrintout, $serialization ) {
		// Implementation omitted.
	}

	public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization ) {
		if ( $previousPrintout === null ) {
			return;
		}

		$param = substr( $param, 1 );
		$parts = explode( '=', $param, 2 );

		if ( !empty( $param ) ) {
			$label = $serialization['printouts'][$previousPrintout]['label'] ?? '';

			// Use helper method to format label.
			$labelToSave = $this->formatLabel( $label, $param );

			// Save the label in serialization.
			$serialization['printouts'][$previousPrintout] = [
				'label' => $labelToSave,
				'params' => []
			];

			if ( count( $parts ) == 2 ) {
				$serialization['printouts'][$previousPrintout]['params'][trim( $parts[0] )] = $parts[1];
			} else {
				$serialization['printouts'][$previousPrintout]['params'][trim( $parts[0] )] = null;
			}
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
		if ( strpos( $label, '#' ) ) {
			$labelToSave = $label . ';' . $param;
			if ( str_contains( $labelToSave, 'width=' ) ) {
				$adjustWidthParam = rtrim( $param, "px" );
				$widthParts = explode( '=', $adjustWidthParam );
				$adjustedHeight = explode( '#', $label );
				return $adjustedHeight[0] . '' . '#' . $widthParts[1] . '' . $adjustedHeight[1];
			} elseif ( str_contains( $labelToSave, 'height=' ) ) {
				$adjustedWidth = rtrim( $label, "px" );
				$heightParts = explode( '=', $param );
				return $adjustedWidth . '' . 'x' . '' . $heightParts[1];
			}
		} else {
			$labelToSave = $label . ' ' . '#' . $param;
			if ( str_contains( $labelToSave, 'width=' ) ) {
				$labelToSave = str_replace( 'width=', '', $labelToSave );
				$labelToSave = str_replace( '=', '', $labelToSave );
				return $labelToSave;
			} elseif ( str_contains( $labelToSave, 'height=' ) ) {
				$labelToSave = str_replace( 'height=', 'x', $labelToSave );
				$labelToSave = str_replace( '=', '', $labelToSave );
				return $labelToSave;
			} else {
				return str_replace( '=', '', $labelToSave );
			}
		}
	}
}
