<?php

namespace SMW\Query\Processor;

/**
 * The class supports formatter option in ask queries
 * to set links, e.g., ?Main Image=|+link=
 *
 * @license GNU GPL v2+
 * @since 5.0.0
 *
 * @author milic
 */
class LinkFormatterOption {

	const FORMAT_LEGACY = 'format.legacy';
	const PRINT_THIS = 'print.this';

	public function getPrintRequestWithOutputMarker( string $param, string $previousPrintout, array $serialization ): array {
		if ( $previousPrintout === null ) {
			return [];
		}

		$param = substr( $param, 1 );

		if ( !empty( $param ) ) {
			$label = $serialization['printouts'][$previousPrintout]['label'] ?? '';
			$params = $serialization['printouts'][$previousPrintout]['params'] ?? '';
			$partsParam = explode( '=', $param, 2 );

			if ( !empty( $params ) ) {
				$params['link'] = $partsParam[1];
			} else {
				$params = [ 'link' => $partsParam[1] ];
			}

			// Use helper method to format label.
			$labelToSave = $this->formatLabel( $label, $param );

			// Save the label and additional params in serialization.
			$serialization['printouts'][$previousPrintout] = [
				'label' => $labelToSave,
				'params' => $params,
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
