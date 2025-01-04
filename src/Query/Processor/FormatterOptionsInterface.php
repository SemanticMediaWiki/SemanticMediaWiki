<?php

namespace SMW\Query\Processor;

use InvalidArgumentException;

/**
 * The interface is used as custom options formatter used in ask queries
 * Custom created formatter like SizeFormatterOption or LinkFormatterOption implements
 * this interface and use its functions for formating options
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
