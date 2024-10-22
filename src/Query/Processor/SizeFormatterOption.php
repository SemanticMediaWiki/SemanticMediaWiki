<?php

namespace SMW\Query\Processor;

/**
 * The class supports size formatter option in ask queries 
 * example ?Main Image=|width=+30px or ?Main Image=|height=+30px
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
        $parts = explode( '=', $param, 2 );
    
        if ( isset( $param ) ) {
            // fetch the previous label
            $label = $serialization[ 'printouts' ][ $previousPrintout ][ 'label' ];

            // check the label and create final label with correct format
            if ( strpos( $label,'#' ) ) {
                $labelToSave = $label . ';' . $param;
                if (str_contains( $labelToSave, 'width=' ) ) {
                    $adjustWidthParam = rtrim( $param, "px" );
                    $widthParts = explode( '=', $adjustWidthParam );
                    $adjustedHeight = explode( '#', $label );
                    $labelToSave = $adjustedHeight[0] . '' . '#' . $widthParts[1] . '' . $adjustedHeight[1];
                } elseif ( str_contains( $labelToSave, 'height=' ) ) {
                    $adjustedWidth = rtrim( $label, "px" );
                    $heightParts = explode( '=', $param );
                    $labelToSave = $adjustedWidth . '' . 'x' . '' . $heightParts[1];
                }
            } else {
                $labelToSave = $label . ' ' . '#' . $param;
                if ( str_contains( $labelToSave, 'width=' ) ) {
                    $labelToSave = str_replace( 'width=', '', $labelToSave );
                    $labelToSave = str_replace( '=', '', $labelToSave );
                } elseif ( str_contains( $labelToSave, 'height=' ) ) {
                    $labelToSave = str_replace( 'height=', 'x', $labelToSave );
                    $labelToSave = str_replace( '=', '', $labelToSave );
                } else {
                  $labelToSave = str_replace( '=', '', $labelToSave );
                }
            }

            // save the label as a part of serialization
            $serialization[ 'printouts' ][ $previousPrintout ] = [
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
}
