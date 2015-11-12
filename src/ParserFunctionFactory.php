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
	private $parser;

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

		$templateRenderer = $applicationFactory->newMwCollaboratorFactory()->newWikitextTemplateRenderer();

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

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function newAskParserFunctionDefinition() {

		// PHP 5.3
		$parserFunctionFactory = $this;

		$askParserFunctionDefinition = function( $parser ) use( $parserFunctionFactory ) {

			$smwgQEnabled = ApplicationFactory::getInstance()->getSettings()->get( 'smwgQEnabled' );
			$askParserFunction = $parserFunctionFactory->newFromParser( $parser )->newAskParserFunction();

			if ( !$smwgQEnabled ) {
				return $askParserFunction->isQueryDisabled();
			}

			return $askParserFunction->parse( func_get_args() );
		};

		return array( 'ask', $askParserFunctionDefinition, 0 );
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function newShowParserFunctionDefinition() {

		// PHP 5.3
		$parserFunctionFactory = $this;

		$showParserFunctionDefinition = function( $parser ) use( $parserFunctionFactory ) {

			$smwgQEnabled = ApplicationFactory::getInstance()->getSettings()->get( 'smwgQEnabled' );
			$showParserFunction = $parserFunctionFactory->newFromParser( $parser )->newShowParserFunction();

			if ( !$smwgQEnabled ) {
				return $showParserFunction->isQueryDisabled();
			}

			return $showParserFunction->parse( func_get_args() );
		};

		return array( 'show', $showParserFunctionDefinition, 0 );
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function newSubobjectParserFunctionDefinition() {

		// PHP 5.3
		$parserFunctionFactory = $this;

		$subobjectParserFunctionDefinition = function( $parser ) use( $parserFunctionFactory ) {

			$subobjectParserFunction = $parserFunctionFactory->newFromParser( $parser )->newSubobjectParserFunction();

			return $subobjectParserFunction->parse(
				ParameterProcessorFactory::newFromArray( func_get_args() )
			);
		};

		return array( 'subobject', $subobjectParserFunctionDefinition, 0 );
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function newRecurringEventsParserFunctionDefinition() {

		// PHP 5.3
		$parserFunctionFactory = $this;

		$recurringEventsParserFunctionDefinition = function( $parser ) use( $parserFunctionFactory ) {

			$recurringEventsParserFunction = $parserFunctionFactory->newFromParser( $parser )->newRecurringEventsParserFunction();

			return $recurringEventsParserFunction->parse(
				ParameterProcessorFactory::newFromArray( func_get_args() )
			);
		};

		return array( 'set_recurring_event', $recurringEventsParserFunctionDefinition, 0 );
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function newSetParserFunctionDefinition() {

		// PHP 5.3
		$parserFunctionFactory = $this;

		$setParserFunctionDefinition = function( $parser ) use( $parserFunctionFactory ) {

			$setParserFunction = $parserFunctionFactory->newFromParser( $parser )->newSetParserFunction();

			return $setParserFunction->parse(
				ParameterProcessorFactory::newFromArray( func_get_args() )
			);
		};

		return array( 'set', $setParserFunctionDefinition, 0 );
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function newConceptParserFunctionDefinition() {

		// PHP 5.3
		$parserFunctionFactory = $this;

		$conceptParserFunctionDefinition = function( $parser ) use( $parserFunctionFactory ) {

			$conceptParserFunction = $parserFunctionFactory->newFromParser( $parser )->newConceptParserFunction();

			return $conceptParserFunction->parse( func_get_args() );
		};

		return array( 'concept', $conceptParserFunctionDefinition, 0 );
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function newDeclareParserFunctionDefinition() {

		// PHP 5.3
		$parserFunctionFactory = $this;

		$declareParserFunctionDefinition = function( $parser, $frame, $args ) use( $parserFunctionFactory ) {
			return $parserFunctionFactory->newFromParser( $parser )->newDeclareParserFunction()->parse( $frame, $args );
		};

		return array( 'declare', $declareParserFunctionDefinition, Parser::SFH_OBJECT_ARGS );
	}

}
