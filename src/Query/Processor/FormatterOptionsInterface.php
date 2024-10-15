<?php

namespace SMW\Query\Processor;

use InvalidArgumentException;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author milic
 */
interface FormatterOptionsInterface {
   
    public function addPrintRequest( $name, $param, $previousPrintout, $serialization );

    public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization );

}
