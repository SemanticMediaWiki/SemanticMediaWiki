<?php

namespace SMW\ParserFunctions;

/**
 * Class that provides the {{#show}} parser function
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ShowParserFunction {

	/**
	 * @var AskParserFunction
	 */
	private $askParserFunction;

	/**
	 * @since 1.9
	 *
	 * @param AskParserFunction $askParserFunction
	 */
	public function __construct( AskParserFunction $askParserFunction ) {
		$this->askParserFunction = $askParserFunction;
	}

	/**
	 * Parse parameters, return results from the query printer and update the
	 * ParserOutput with meta data from the query
	 *
	 * @note The {{#show}} parser function internally uses the AskParserFunction
	 * and while an extra ShowParserFunction constructor is not really necessary
	 * it allows for separate unit testing
	 *
	 * @since 1.9
	 *
	 * @param array $rawParams
	 *
	 * @return string|null
	 */
	public function parse( array $rawParams ) {
		$this->askParserFunction->setShowMode( true );
		return $this->askParserFunction->parse( $rawParams );
	}

	/**
	 * Returns a message about inline queries being disabled
	 *
	 * @see $smwgQEnabled
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	public function isQueryDisabled() {
		return $this->askParserFunction->isQueryDisabled();
	}

}
