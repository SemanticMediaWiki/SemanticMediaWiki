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

			if ( strpos( $label, '=' ) !== true ) {
				if ( str_contains( $label, 'px' ) ) {
					$mainLabel = $serialization['printouts'][$previousPrintout]['mainLabel'] ?? '';
				} else {
					$mainLabel = $label;
				}
			}

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
				'params' => $params,
				'mainLabel' => $mainLabel
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
		$partsLabel = explode( '=', $label );
		$paramParts = explode( '=', $param );

		if ( strpos( $label, '#' ) !== false ) {
			$paramParts = explode( '=', $param );
			if ( count( $paramParts ) >= 2 ) {
				if ( strpos( $param, 'width=' ) !== false ) {
					$adjustedWidth = rtrim( explode( '=', $param )[1], 'px' );
					$parts = explode( '#', $label );
					return ( isset( $parts[0] ) ? $parts[0] : '' ) . '#' . $adjustedWidth . 'x' . ( isset( $parts[1] ) ? $parts[1] : '' );
				}

				if ( strpos( $param, 'height=' ) !== false ) {
					$label = str_replace( "=","", $label );
					$parts = explode( '#', $label );
					$adjustedHeight = explode( '=', $param )[1];
					$adjustedWidth = rtrim( $parts[1], 'px' );
					return $parts[0] . '#' . $adjustedWidth . 'x' . $adjustedHeight;
				}

				return $label . ';' . $param;
			}
		} else { 
			$labelToSave = $label . ' ' . '#' . $param;
			if ( count( $partsLabel ) === 1 ) {
				$splitLabel = explode( '#', $labelToSave, 2 );
					if ( !strpos( $splitLabel[0], '=' )) {
						return $labelToSave = $splitLabel[0] . '#' . $paramParts[1];
					}
			} else {
				$parts = explode( '=', $labelToSave );
				if ( count( $parts ) === 1 ) {
					return str_replace( '=', '', $labelToSave );
				} else {
					if ($partsLabel[0] !== '' && count( $parts ) >= 2) {
						return $labelToSave = $partsLabel[0] . ' #'. $paramParts[1] . '=' . $partsLabel[1];
					} else if ( count( $partsLabel ) === 1 ) {
						$splitLabel = explode( '#', $labelToSave, 2 );
						if ( !strpos( $splitLabel[0], '=' )) {
							return $labelToSave = $splitLabel[0] . '+' . '#' . $splitLabel[1];
						}
					} else {
						return $labelToSave = '#'. $paramParts[1] . $label;
					}
				}
			}
		}
	}
}
