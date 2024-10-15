<?php

namespace SMW\Query\Processor;

/**
 * The class supports Main Image formatter option in ask queries 
 * image size ?Main Image=|+30px
 * The class implements FormatterOptionsInterface which holds the 
 * functions for adding print requests and handling parameters
 * 
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author milic
 */
class MainImageValueFormatter implements FormatterOptionsInterface {

    /**
	 * Format type
	 */
	const FORMAT_LEGACY = 'format.legacy';

	/**
	 * Identify the PrintThis instance
	 */
	const PRINT_THIS = 'print.this';

    /**
     * Add a print request to the image formatter options.
     *
     * @param string $name The name of the print request.
     * @param mixed $param Additional parameters for the request.
     * @param mixed $previousPrintout Data from previous printouts.
     * @param mixed $serialization Information for serialization.
     *
     * @return array The updated serialization array.
     * @throws InvalidArgumentException if arguments are invalid.
     */
    public function addPrintRequest( $name, $param, $previousPrintout, $serialization ) {
         // Remove the leading '?' from the parameter
         $param = substr( $param, 1 ); // "Main Image"

         // Validate that the param is 'Main Image'
         if ( !str_contains($param, 'Main Image') ) {
             throw new InvalidArgumentException( 'Expected "Main Image" as the parameter.' );
         }
 
         // Create a hash for the 'Main Image' print request
         $hash = md5( json_encode( $param ) . $name );
         $previousPrintout = $hash;
 
         $serialization['printouts'][$hash] = [
			'label' => $param,
			'params' => []
		];

         // Prepare both return values
        $updatedSerialization = $serialization;
        $updatedPreviousPrintout = $previousPrintout;

        return [
            'serialization' => $updatedSerialization,
            'previousPrintout' => $updatedPreviousPrintout,
        ];
    }

    public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization ) {
        if ( $previousPrintout === null ) {
			return;
		}

		$param = substr( $param, 1 );

        if ( true ) {
            $label = $serialization['printouts'][$previousPrintout]['label'];

            if (strpos($label,'#')) {
                $labelToSave = $label . ';' . $param;
            } else {
                $labelToSave = $label . ' ' . '#' . $param;
                $labelToSave = str_replace( '=', '', $labelToSave );
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