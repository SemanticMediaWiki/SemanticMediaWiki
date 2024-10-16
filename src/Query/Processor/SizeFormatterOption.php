<?php

namespace SMW\Query\Processor;

/**
 * The class supports size formatter option in ask queries 
 * example ?Main Image=|width=+30px
 * The class implements FormatterOptionsInterface which holds the 
 * functions for adding print requests and handling parameters
 * 
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author milic
 */
class SizeFormatterOption implements FormatterOptionsInterface {

    /**
	 * Format type
	 */
	const FORMAT_LEGACY = 'format.legacy';

	/**
	 * Identify the PrintThis instance
	 */
	const PRINT_THIS = 'print.this';

    public function addPrintRequest( $name, $param, $previousPrintout, $serialization ) {}

    public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization ) {
        if ( $previousPrintout === null ) {
			return;
		}

		$param = substr( $param, 1 );

        if ( isset( $param ) ) {
            $label = $serialization['printouts'][$previousPrintout]['label'];

            if (strpos($label,'#')) {
                $labelToSave = $label . ';' . $param;
                if (str_contains($labelToSave, 'width=')) {
                    $labelToSave = str_replace('width=', '', $labelToSave);
                } elseif ( str_contains($labelToSave, 'height=') ) {
                    $labelToSave = str_replace('height=', '', $labelToSave);
                }
            } else {
                $labelToSave = $label . ' ' . '#' . $param;
                if (str_contains($labelToSave, 'width=')) {
                    $labelToSave = str_replace('width=', '', $labelToSave);
                    $labelToSave = str_replace( '=', '', $labelToSave );
                } elseif ( str_contains($labelToSave, 'height=') ) {
                    $labelToSave = str_replace('height=', '', $labelToSave);
                    $labelToSave = str_replace( '=', '', $labelToSave );
                } else {
                  $labelToSave = str_replace( '=', '', $labelToSave );
                }
            }

            $serialization['printouts'][$previousPrintout] = [
                'label' => $labelToSave,
                'params' => []
            ];
        } 

        return [
            'serialization' => $serialization,
            'previousPrintout' => $previousPrintout,
        ];
    }
}
