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

		$mainLabel = '';
		if ( !empty( $param ) ) {
			$param = substr( $param, 1 );

			$parts = explode( '=', $param, 2 );
			$label = $serialization['printouts'][$previousPrintout]['label'] ?? '';
			$params = $serialization['printouts'][$previousPrintout]['params'] ?? '';

			if ( str_contains( $label, '=' ) ) {
				if ( str_contains( $label, 'px' ) ) {
					$mainLabel = $serialization['printouts'][$previousPrintout]['mainLabel'] ?? '';
				} else {
					$mainLabel = $label;
				}
			}

			if ( !empty( $params ) ) {
				$params[ $parts[0] ] = $parts[1];
			} else {
				$params = [ $parts[0] => $parts[1] ];
			}

			// Use helper method to format label.
			$labelToSave = $this->formatLabel( $label, $param, $mainLabel );

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

	private function formatLabel( $label, $param, $mainLabel ): string {
		$partsLabel = explode( '=', $label );
		$paramParts = explode( '=', $param );

		if ( strpos( $label, '#' ) !== false ) {
			if ( count( $paramParts ) >= 2 ) {
				if ( strpos( $param, 'width=' ) !== false ) {
					$adjustedWidth = rtrim( explode( '=', $param )[1], 'px' );
					$parts = explode( '#', $label );
					return ( isset( $parts[0] ) ? $parts[0] : '' ) . '#' . $adjustedWidth . 'x' . ( isset( $parts[1] ) ? $parts[1] : '' );
				}
				if ( strpos( $param, 'height=' ) !== false ) {
					$adjustedHeight = explode( '=', $param )[1];

					if ( count( $partsLabel ) > 1 ) {
						if ( strpos( $mainLabel, '=' ) !== false ) {
							$firstPart = rtrim( $partsLabel[0], 'px' );
							return rtrim( $partsLabel[0], 'px' ) . 'x' . $adjustedHeight . '=' . $partsLabel[1];
						}
					} else {
						$parts = explode( '#', $label );
						$adjustedWidth = rtrim( $parts[1], 'px' );
						if ( strpos( $mainLabel, '=' ) !== false ) {
							return $parts[0] . '#' . $adjustedWidth . 'x' . $adjustedHeight . '=';
						}
					}
					return $parts[0] . '#' . $adjustedWidth . 'x' . $adjustedHeight;
				}

				return $label . ';' . $param;
			}
		} else {
			$labelToSave = $label . ' ' . '#' . $param;
			if ( count( $partsLabel ) === 1 ) {
				$splitLabel = explode( '#', $labelToSave, 2 );
				if ( !str_contains( $splitLabel[0], '=' ) ) {
					if ( strpos( $paramParts[0], 'height' ) !== false ) {
						return $labelToSave = $partsLabel[0] . ' #x' . $paramParts[1];
					}
					return $labelToSave = $splitLabel[0] . '#' . $paramParts[1];
				}
			} else {
				$parts = explode( '=', $labelToSave );
				if ( count( $parts ) === 1 ) {
					return str_replace( '=', '', $labelToSave );
				} else {
					if ( $partsLabel[0] !== '' && count( $parts ) >= 2 ) {
						if ( strpos( $paramParts[0], 'height' ) !== false ) {
							return $labelToSave = $partsLabel[0] . ' #x' . $paramParts[1] . '=' . $partsLabel[1];
						}
						return $labelToSave = $partsLabel[0] . ' #' . $paramParts[1] . '=' . $partsLabel[1];
					} elseif ( count( $partsLabel ) === 1 ) {
						$splitLabel = explode( '#', $labelToSave, 2 );
						if ( !str_contains( $splitLabel[0], '=' ) ) {
							return $labelToSave = $splitLabel[0] . '+' . '#' . $splitLabel[1];
						}
					} else {
						return $labelToSave = '#' . $paramParts[1] . $label;
					}
				}
			}
		}
	}
}
