<?php

namespace SMW\Query\Processor;

/**
 * The class supports formatter option to format table headers
 *
 * @license GNU GPL v2+
 * @since 5.0.0
 *
 * @author milic
 */
class TableHeaderFormatterOption {

	const FORMAT_LEGACY = 'format.legacy';
	const PRINT_THIS = 'print.this';

	public function getPrintRequestWithOutputMarker( string $param, string $previousPrintout, array $serialization ): array {
		if ( $previousPrintout === null ) {
			return [];
		}

		if ( !empty( $param ) ) {
			$param = substr( $param, 1 );
			$partsParam = explode( '=', $param, 2 );

			$label = $serialization['printouts'][$previousPrintout]['label'] ?? '';
			$params = $serialization['printouts'][$previousPrintout]['params'] ?? '';
			$mainLabel = $serialization['printouts'][$previousPrintout]['mainLabel'] ?? '';

			if ( str_contains( $label, '=' ) ) {
				if ( str_contains( $label, 'px' ) ) {
					$mainLabel = $serialization['printouts'][$previousPrintout]['mainLabel'] ?? '';
				} else {
					$mainLabel = $label;
				}
			}

			if ( !empty( $params ) ) {
				$params['thclass'] = $partsParam[1];
			} else {
				$params = [ 'thclass' => $partsParam[1] ];
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

		if ( isset( $partsLabel[1] ) && $partsLabel[1] === '' && strpos( $partsLabel[0], '#' ) === false ) {
			if ( count( $partsLabel ) > 1 ) {
				if ( $partsLabel[1] !== '' ) {
					return str_replace( '=', '', $label ) . ' ' . '#' . $paramParts[0] . '=' . $partsLabel[1];
				}
				return str_replace( '=', '', $label ) . ' ' . '#' . $paramParts[0] . '=';
			}
			return str_replace( '=', '', $label . ' ' . '#' . $param );
		} else {
			if ( strpos( $label, '#' ) !== false ) {
				if ( count( $partsLabel ) > 1 ) {
					if ( $partsLabel[1] !== '' ) {
						return $partsLabel[0] . ';' . $paramParts[0] . '=' . $partsLabel[1];
					}
					return $partsLabel[0] . ';' . $paramParts[0] . '=';
				} else {
					if ( str_contains( $mainLabel, '=' ) ) {
						$labelParts = explode( ' ', $partsLabel[0] );
						return $labelParts[1] . ';' . $paramParts[0] . '=' . $labelParts[0];
					}
					return str_replace( '=', '', $label . ';' . $paramParts[0] );
				}
			} else {
				$labelToSave = $label . ' ' . '#' . $param;
				$parts = explode( '=', $labelToSave );
				if ( count( $parts ) === 1 ) {
					return str_replace( '=', '', $labelToSave );
				} else {
					if ( count( $partsLabel ) === 1 ) {
						return $labelToSave = $partsLabel[0] . ' #' . $paramParts[0];
					}
					if ( $partsLabel[0] !== '' ) {
						return $labelToSave = $partsLabel[0] . ' #' . $paramParts[0] . '=' . $partsLabel[1];
					} else {
						return $labelToSave = '#' . $paramParts[0] . $label;
					}
				}
			}
		}
	}
}
