<?php

namespace SMW\Query\Processor;

/**
 * The interface is used as custom options formatter used in ask queries
 * Custom created formatter like SizeFormatter, LinkFormatter and TableHeaderFormatter
 * implements this interface and use its functions for formating options
 *
 * @license GNU GPL v2+
 * @since 5.0.0
 *
 * @author milic
 */
interface FormatterOptionsInterface {

	public function addPrintRequestHandleParams( $name, $param, $previousPrintout, $serialization );
}
