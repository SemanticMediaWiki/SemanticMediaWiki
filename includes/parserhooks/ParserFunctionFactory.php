<?php

namespace SMW;

use Parser;

/**
 * Factory class for convenience parser function instantiation
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:ParserFunction
 *
 * @ingroup ParserFunction
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ParserFunctionFactory implements ContextAware {

	/** @var Parser */
	protected $parser;

	/** @var ContextResource */
	protected $context = null;

	/**
	 * @since 1.9
	 *
	 * @param Parser $parser
	 * @param ContextResource|null $context
	 */
	public function __construct( Parser $parser, ContextResource $context = null ) {
		$this->parser = $parser;
		$this->context = $context;
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
	 * @see ContextAware::withContext
	 *
	 * @since 1.9
	 *
	 * @return ContextResource
	 */
	public function withContext() {

		if ( $this->context === null ) {
			$this->context = new ExtensionContext();
		}

		return $this->context;
	}

	/**
	 * Convenience instantiation of a SubobjectParserFunction object
	 *
	 * @since 1.9
	 *
	 * @return SubobjectParserFunction
	 */
	public function getSubobjectParser() {
		return $this->withContext()->getDependencyBuilder()->newObject( 'SubobjectParserFunction', array( 'Parser' => $this->parser ) );
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
