<?php

namespace SMW;

use Parser;
use Title;
use ParserOutput;

/**
 * Class that provides the {{#show}} parser function
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Class that provides the {{#show}} parser function
 *
 * @ingroup ParserFunction
 */
class ShowParserFunction {

	/** @var IParserData */
	protected $parserData;

	/** @var QueryData */
	protected $queryData;

	/** @var MessageFormatter */
	protected $msgFormatter;

	/**
	 * @since 1.9
	 *
	 * @param IParserData $parserData
	 * @param QueryData $queryData
	 * @param MessageFormatter $messageList
	 */
	public function __construct( IParserData $parserData, QueryData $queryData, MessageFormatter $msgFormatter ) {
		$this->parserData = $parserData;
		$this->queryData = $queryData;
		$this->msgFormatter = $msgFormatter;
	}

	/**
	 * Returns a message about inline queries being disabled
	 * @see $smwgQEnabled
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	protected function disabled() {
		return $this->msgFormatter->addFromKey( 'smw_iq_disabled' )->getHtml();
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
		$ask = new AskParserFunction( $this->parserData, $this->queryData, $this->msgFormatter );
		return $ask->useShowMode()->parse( $rawParams );
	}

	/**
	 * Parser::setFunctionHook {{#show}} handler method
	 *
	 * @since 1.9
	 *
	 * @param Parser $parser
	 *
	 * @return string
	 */
	public static function render( Parser &$parser ) {
		$show = new self(
			new ParserData( $parser->getTitle(), $parser->getOutput() ),
			new QueryData( $parser->getTitle() ),
			new MessageFormatter( $parser->getTargetLanguage() )
		);
		return $GLOBALS['smwgQEnabled'] ? $show->parse( func_get_args() ) : $show->disabled();
	}
}
