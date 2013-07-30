<?php

namespace SMW;

use Parser;

/**
 * Factory class for convenience parser function instantiation
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Factory class for convenience parser function instantiation
 *
 * @ingroup ParserFunction
 */
class ParserFunctionFactory {

	/** @var Parser */
	protected $parser;

	/**
	 * @since 1.9
	 *
	 * @param Parser $parser
	 */
	public function __construct( Parser $parser ) {
		$this->parser = $parser;
	}

	/**
	 * Convenience instantiation of a ParserFunctionFactory object
	 *
	 * @since 1.9
	 *
	 * @param Parser $parser
	 *
	 * @return ParserFunctionFactory
	 */
	public static function newFromParser( Parser $parser ) {
		return new self( $parser );
	}

	/**
	 * Convenience instantiation of a SubobjectParserFunction object
	 *
	 * @since 1.9
	 *
	 * @return SubobjectParserFunction
	 */
	public function getSubobjectParser() {
		return new SubobjectParserFunction(
			new ParserData( $this->parser->getTitle(), $this->parser->getOutput() ),
			new Subobject( $this->parser->getTitle() ),
			new MessageFormatter( $this->parser->getTargetLanguage() )
		);
	}

	/**
	 * Convenience instantiation of a RecurringEventsParserFunction object
	 *
	 * @since 1.9
	 *
	 * @return RecurringEventsParserFunction
	 */
	public function getRecurringEventsParser() {
		return new RecurringEventsParserFunction(
			new ParserData( $this->parser->getTitle(), $this->parser->getOutput() ),
			new Subobject( $this->parser->getTitle() ),
			new MessageFormatter( $this->parser->getTargetLanguage() ),
			Settings::newFromGlobals()
		);
	}
}
