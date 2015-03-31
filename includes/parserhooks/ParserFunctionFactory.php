<?php

namespace SMW;

use Parser;

/**
 * @see http://www.semantic-mediawiki.org/wiki/Help:ParserFunction
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ParserFunctionFactory {

	/**
	 * @var Parser
	 */
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
	 * @deprecated since 2.1, use newSubobjectParserFunction
	 */
	public function getSubobjectParser() {
		return $this->newSubobjectParserFunction();
	}

	/**
	 * @deprecated since 2.1, use newRecurringEventsParserFunction
	 */
	public function getRecurringEventsParser() {
		return $this->newRecurringEventsParserFunction();
	}

	/**
	 * @since 2.1
	 *
	 * @return AskParserFunction
	 */
	public function newAskParserFunction() {

		$circularReferenceGuard = new CircularReferenceGuard( 'ask-parser' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		$messageFormatter = new MessageFormatter( $this->parser->getTargetLanguage() );

		$instance = new AskParserFunction(
			$parserData,
			$messageFormatter,
			$circularReferenceGuard
		);

		return $instance;
	}

	/**
	 * @since 2.1
	 *
	 * @return ShowParserFunction
	 */
	public function newShowParserFunction() {

		$circularReferenceGuard = new CircularReferenceGuard( 'show-parser' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		$messageFormatter = new MessageFormatter( $this->parser->getTargetLanguage() );

		$instance = new ShowParserFunction(
			$parserData,
			$messageFormatter,
			$circularReferenceGuard
		);

		return $instance;
	}

	/**
	 * @since 2.1
	 *
	 * @return SetParserFunction
	 */
	public function newSetParserFunction() {

		$applicationFactory = ApplicationFactory::getInstance();

		$parserData = $applicationFactory->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		$messageFormatter = new MessageFormatter(
			$this->parser->getTargetLanguage()
		);

		$templateRenderer = $applicationFactory
			->newMwCollaboratorFactory()
			->newHtmlTemplateRenderer( $this->parser );

		$instance = new SetParserFunction(
			$parserData,
			$messageFormatter,
			$templateRenderer
		);

		return $instance;
	}

	/**
	 * @since 2.1
	 *
	 * @return ConceptParserFunction
	 */
	public function newConceptParserFunction() {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		$messageFormatter = new MessageFormatter( $this->parser->getTargetLanguage() );

		$instance = new ConceptParserFunction(
			$parserData,
			$messageFormatter
		);

		return $instance;
	}

	/**
	 * @since 2.1
	 *
	 * @return SubobjectParserFunction
	 */
	public function newSubobjectParserFunction() {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		$subobject = new Subobject( $this->parser->getTitle() );
		$messageFormatter = new MessageFormatter( $this->parser->getTargetLanguage() );

		$instance = new SubobjectParserFunction(
			$parserData,
			$subobject,
			$messageFormatter
		);

		return $instance;
	}

	/**
	 * @since 2.1
	 *
	 * @return RecurringEventsParserFunction
	 */
	public function newRecurringEventsParserFunction() {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		$subobject = new Subobject( $this->parser->getTitle() );
		$messageFormatter = new MessageFormatter( $this->parser->getTargetLanguage() );

		$instance = new RecurringEventsParserFunction(
			$parserData,
			$subobject,
			$messageFormatter,
			ApplicationFactory::getInstance()->getSettings()
		);

		return $instance;
	}

	/**
	 * @since 2.1
	 *
	 * @return DeclareParserFunction
	 */
	public function newDeclareParserFunction() {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		$instance = new DeclareParserFunction(
			$parserData
		);

		return $instance;
	}

}
