<?php

namespace SMW;

/**
 * Class that provides the {{#show}} parser function
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ShowParserFunction {

	/** @var ParserData */
	protected $parserData;

	/** @var ContextResource */
	protected $context;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param ContextResource $context
	 */
	public function __construct( ParserData $parserData, ContextResource $context ) {
		$this->parserData = $parserData;
		$this->context = $context;
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
	 * @param array $params
	 *
	 * @return string|null
	 */
	public function parse( array $rawParams ) {
		$ask = new AskParserFunction( $this->parserData, $this->context );
		return $ask->setShowMode( true )->parse( $rawParams );
	}

	/**
	 * Returns a message about inline queries being disabled
	 * @see $smwgQEnabled
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	public function isQueryDisabled() {
		return $this->context->getDependencyBuilder()
			->newObject( 'MessageFormatter' )
			->addFromKey( 'smw_iq_disabled' )
			->getHtml();
	}

}
