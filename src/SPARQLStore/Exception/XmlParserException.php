<?php

namespace SMW\SPARQLStore\Exception;

/**
 * @ingroup Sparql
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class XmlParserException extends \Exception {

	/**
	 * @since  2.1
	 *
	 * @param string $errorText
	 * @param int $errorLine
	 * @param int $errorColumn
	 */
	public function __construct( $errorText, $errorLine, $errorColumn ) {
		parent::__construct( "Failed with $errorText on line $errorLine and column $errorColumn .\n" );
	}

}
