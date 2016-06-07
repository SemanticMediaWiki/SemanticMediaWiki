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
		return $this->newSubobjectParserFunction( $this->parser );
	}

	/**
	 * @deprecated since 2.1, use newRecurringEventsParserFunction
	 */
	public function getRecurringEventsParser() {
		return $this->newRecurringEventsParserFunction( $this->parser );
	}

	/**
	 * @since 2.1
	 *
	 * @param Parser $parser
	 *
	 * @return AskParserFunction
	 */
	public function newAskParserFunction( Parser $parser ) {

		$circularReferenceGuard = new CircularReferenceGuard( 'ask-parser' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$messageFormatter = new MessageFormatter(
			$parser->getTargetLanguage()
		);

		$askParserFunction = new AskParserFunction(
			$parserData,
			$messageFormatter,
			$circularReferenceGuard
		);

		return $askParserFunction;
	}

	/**
	 * @since 2.1
	 *
	 * @param Parser $parser
	 *
	 * @return ShowParserFunction
	 */
	public function newShowParserFunction( Parser $parser ) {

		$circularReferenceGuard = new CircularReferenceGuard( 'show-parser' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$messageFormatter = new MessageFormatter(
			$parser->getTargetLanguage()
		);

		$showParserFunction = new ShowParserFunction(
			$parserData,
			$messageFormatter,
			$circularReferenceGuard
		);

		return $showParserFunction;
	}

	/**
	 * @since 2.1
	 *
	 * @param Parser $parser
	 *
	 * @return SetParserFunction
	 */
	public function newSetParserFunction( Parser $parser ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$parserData = $applicationFactory->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$messageFormatter = new MessageFormatter(
			$parser->getTargetLanguage()
		);

		$templateRenderer = $applicationFactory->newMwCollaboratorFactory()->newWikitextTemplateRenderer();

		$setParserFunction = new SetParserFunction(
			$parserData,
			$messageFormatter,
			$templateRenderer
		);

		return $setParserFunction;
	}

	/**
	 * @since 2.1
	 *
	 * @param Parser $parser
	 *
	 * @return ConceptParserFunction
	 */
	public function newConceptParserFunction( Parser $parser ) {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$messageFormatter = new MessageFormatter(
			$parser->getTargetLanguage()
		);

		$conceptParserFunction = new ConceptParserFunction(
			$parserData,
			$messageFormatter
		);

		return $conceptParserFunction;
	}

	/**
	 * @since 2.1
	 *
	 * @param Parser $parser
	 *
	 * @return SubobjectParserFunction
	 */
	public function newSubobjectParserFunction( Parser $parser ) {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$subobject = new Subobject( $parser->getTitle() );

		$messageFormatter = new MessageFormatter(
			$parser->getTargetLanguage()
		);

		$subobjectParserFunction = new SubobjectParserFunction(
			$parserData,
			$subobject,
			$messageFormatter
		);

		return $subobjectParserFunction;
	}

	/**
	 * @since 2.1
	 *
	 * @param Parser $parser
	 *
	 * @return RecurringEventsParserFunction
	 */
	public function newRecurringEventsParserFunction( Parser $parser ) {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$subobject = new Subobject( $parser->getTitle() );

		$messageFormatter = new MessageFormatter(
			$parser->getTargetLanguage()
		);

		$recurringEventsParserFunction = new RecurringEventsParserFunction(
			$parserData,
			$subobject,
			$messageFormatter,
			ApplicationFactory::getInstance()->getSettings()
		);

		return $recurringEventsParserFunction;
	}

	/**
	 * @since 2.1
	 *
	 * @param Parser $parser
	 *
	 * @return DeclareParserFunction
	 */
	public function newDeclareParserFunction( Parser $parser ) {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$declareParserFunction = new DeclareParserFunction(
			$parserData
		);

		return $declareParserFunction;
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

			$askParserFunction = $parserFunctionFactory->newAskParserFunction(
				$parser
			);

			if ( !$smwgQEnabled ) {
				return ApplicationFactory::getInstance()->getSettings()->get( 'smwgInlineErrors' ) ? $askParserFunction->isQueryDisabled(): '';
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

			$showParserFunction = $parserFunctionFactory->newShowParserFunction(
				$parser
			);

			if ( !$smwgQEnabled ) {
				return ApplicationFactory::getInstance()->getSettings()->get( 'smwgInlineErrors' ) ? $showParserFunction->isQueryDisabled(): '';
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

			$subobjectParserFunction = $parserFunctionFactory->newSubobjectParserFunction(
				$parser
			);

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

			$recurringEventsParserFunction = $parserFunctionFactory->newRecurringEventsParserFunction(
				$parser
			);

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

			$setParserFunction = $parserFunctionFactory->newSetParserFunction(
				$parser
			);

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

			$conceptParserFunction = $parserFunctionFactory->newConceptParserFunction(
				$parser
			);

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

			$declareParserFunction = $parserFunctionFactory->newDeclareParserFunction(
				$parser
			);

			return $declareParserFunction->parse( $frame, $args );
		};

		return array( 'declare', $declareParserFunctionDefinition, Parser::SFH_OBJECT_ARGS );
	}

}
