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
			// $param = str_replace( 'thclass=', 'class', $param );
			$partsParam = explode( '=', $param, 2 );

			$label = $serialization['printouts'][$previousPrintout]['label'] ?? '';
			$params = $serialization['printouts'][$previousPrintout]['params'] ?? '';

			if ( !empty( $params ) ) {
				$params['thclass'] = $partsParam[1];
			} else {
				$params = [ 'thclass' => $partsParam[1] ];
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
		$partsLabel = explode( '=', $label );
		$paramParts = explode( '=', $param );

		if ( isset( $partsLabel[1] ) && $partsLabel[1] === '' && strpos( $partsLabel[0], '#' ) === false ) {
			return str_replace( '=', '', $label . ' ' . '#' . $param );
		} else {
			if ( strpos( $label, '#' ) !== false ) {
				$parts = explode( '=', $label );
				if ( count( $parts ) > 1 ) {
					return $parts[0] . ';' . $param . '=' . $parts[1];
				} else {
					return str_replace( '=', '', $label . ';' . $paramParts[0] );
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
