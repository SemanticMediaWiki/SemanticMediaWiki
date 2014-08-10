<?php

namespace SMW\SPARQLStore\Exception;

/**
 * @ingroup Sparql
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class XmlParserException extends \Exception {

	/**
	 * @since  2.1
	 *
	 * @param string $errorText
	 * @param integer $errorLine
	 * @param integer $errorColumn
	 */
	public function __construct( $errorText, $errorLine, $errorColumn ) {
		parent::__construct( "Failed with $errorText on line $errorLine and column $errorColumn .\n" );
	}

}
