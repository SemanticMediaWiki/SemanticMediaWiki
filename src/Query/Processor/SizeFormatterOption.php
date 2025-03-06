<?php

namespace SMW\Query\Processor;

/**
 * The class supports size formatter option in ask queries
 * example ?Main Image=|width=+30px or ?Main Image=|height=+30px
 *
 *
 * @license GNU GPL v2+
 * @since 5.0.0
 *
 * @author milic
 */
class SizeFormatterOption {

	const FORMAT_LEGACY = 'format.legacy';
	const PRINT_THIS = 'print.this';

	public function getPrintRequestWithOutputMarker( string $param, string $previousPrintout, array $serialization ): array {
		if ( $previousPrintout === null ) {
			return [];
		}

		if ( !empty( $param ) ) {
			$param = substr( $param, 1 );

			$parts = explode( '=', $param, 2 );
			$label = $serialization['printouts'][$previousPrintout]['label'] ?? '';
			$params = $serialization['printouts'][$previousPrintout]['params'] ?? '';

			if ( !empty( $params ) ) {
				$params[ $parts[0] ] = $parts[1];
			} else {
				$params = [ $parts[0] => $parts[1] ];
			}

			// Use helper method to format label.
			$labelToSave = $this->formatLabel( $label, $param );

			// Save the label and additional params in serialization.
			$serialization['printouts'][$previousPrintout] = [
				'label' => $labelToSave,
				'params' => $params
			];

		} else {
			$serialization['printouts'][$previousPrintout]['params'] = null;
		}

		return [
			'serialization' => $serialization,
			'previousPrintout' => $previousPrintout,
		];
	}

	private function formatLabel( $label, $param ): string {
		if ( strpos( $label, '#' ) !== false ) {
			$paramParts = explode( '=', $param );
			if ( count( $paramParts ) >= 2 ) {
				if ( strpos( $param, 'width=' ) !== false ) {
					$adjustedWidth = rtrim( explode( '=', $param )[1], 'px' );
					$parts = explode( '#', $label );
					return ( isset( $parts[0] ) ? $parts[0] : '' ) . '#' . $adjustedWidth . ( isset( $parts[1] ) ? $parts[1] : '' );
				}

				if ( strpos( $param, 'height=' ) !== false ) {
					$adjustedHeight = explode( '=', $param )[1];
					$adjustedWidth = rtrim( $label, 'px' );
					return $adjustedWidth . 'x' . $adjustedHeight;
				}

				return $label . ';' . $param;
			}
		}

		$labelToSave = $label . ' #' . $param;
		$labelToSave = str_replace( [ 'width=', 'height=' ], [ '', 'x' ], $labelToSave );
		return str_replace( '=', '', $labelToSave );
	}
}
