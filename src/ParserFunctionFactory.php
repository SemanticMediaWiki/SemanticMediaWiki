<?php

namespace SMW;

// Fatal error: Cannot use SMW\ParserFunctions\SubobjectParserFunction as SubobjectParserFunction because the name is already in use
use Parser;
use SMW\Parser\RecursiveTextProcessor;
use SMW\ParserFunctions\AskParserFunction;
use SMW\ParserFunctions\ConceptParserFunction;
use SMW\ParserFunctions\DeclareParserFunction;
use SMW\ParserFunctions\ExpensiveFuncExecutionWatcher;
use SMW\ParserFunctions\RecurringEventsParserFunction as RecurringEventsParserFunc;
use SMW\ParserFunctions\SetParserFunction;
use SMW\ParserFunctions\ShowParserFunction;
use SMW\ParserFunctions\SubobjectParserFunction as SubobjectParserFunc;
use SMW\Utils\CircularReferenceGuard;

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
	 * @param Parser|null $parser
	 */
	public function __construct( Parser $parser = null ) {
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
	 * @since 3.0
	 *
	 * @param Parser $parser
	 */
	public function registerFunctionHandlers( Parser $parser ) {

		list( $name, $definition, $flag ) = $this->getAskParserFunctionDefinition();
		$parser->setFunctionHook( $name, $definition, $flag );

		list( $name, $definition, $flag ) = $this->getShowParserFunctionDefinition();
		$parser->setFunctionHook( $name, $definition, $flag );

		list( $name, $definition, $flag ) = $this->getSubobjectParserFunctionDefinition();
		$parser->setFunctionHook( $name, $definition, $flag );

		list( $name, $definition, $flag ) = $this->getSetRecurringEventParserFunctionDefinition();
		$parser->setFunctionHook( $name, $definition, $flag );

		list( $name, $definition, $flag ) = $this->getSetParserFunctionDefinition();
		$parser->setFunctionHook( $name, $definition, $flag );

		list( $name, $definition, $flag ) = $this->getConceptParserFunctionDefinition();
		$parser->setFunctionHook( $name, $definition, $flag );

		list( $name, $definition, $flag ) = $this->getDeclareParserFunctionDefinition();
		$parser->setFunctionHook( $name, $definition, $flag );
	}

	/**
	 * @since 2.1
	 *
	 * @param Parser $parser
	 *
	 * @return AskParserFunction
	 */
	public function newAskParserFunction( Parser $parser ) {

		$applicationFactory =  ApplicationFactory::getInstance();

		$circularReferenceGuard = new CircularReferenceGuard( 'ask-parser' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$parserData = $applicationFactory->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		if ( isset( $parser->getOptions()->smwAskNoDependencyTracking ) ) {
			$parserData->setOption( $parserData::NO_QUERY_DEPENDENCY_TRACE, $parser->getOptions()->smwAskNoDependencyTracking );
		}

		// Avoid possible actions during for example stashedit etc.
		$parserData->setOption( 'request.action', $GLOBALS['wgRequest']->getVal( 'action' ) );

		$parserData->setParserOptions(
			$parser->getOptions()
		);

		$messageFormatter = new MessageFormatter(
			$parser->getTargetLanguage()
		);

		$expensiveFuncExecutionWatcher = new ExpensiveFuncExecutionWatcher(
			$parserData
		);

		$expensiveFuncExecutionWatcher->setExpensiveThreshold(
			$applicationFactory->getSettings()->get( 'smwgQExpensiveThreshold' )
		);

		$expensiveFuncExecutionWatcher->setExpensiveExecutionLimit(
			$applicationFactory->getSettings()->get( 'smwgQExpensiveExecutionLimit' )
		);

		$askParserFunction = new AskParserFunction(
			$parserData,
			$messageFormatter,
			$circularReferenceGuard,
			$expensiveFuncExecutionWatcher
		);

		$askParserFunction->setPostProcHandler(
			$applicationFactory->create( 'PostProcHandler', $parser->getOutput() )
		);

		$askParserFunction->setRecursiveTextProcessor(
			new RecursiveTextProcessor( $parser )
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

		$showParserFunction = new ShowParserFunction(
			$this->newAskParserFunction( $parser )
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

		$mediaWikiCollaboratorFactory = $applicationFactory->newMwCollaboratorFactory();

		$stripMarkerDecoder = $mediaWikiCollaboratorFactory->newStripMarkerDecoder(
			$parser->mStripState
		);

		$setParserFunction = new SetParserFunction(
			$parserData,
			$messageFormatter,
			$mediaWikiCollaboratorFactory->newWikitextTemplateRenderer()
		);

		$setParserFunction->setStripMarkerDecoder(
			$stripMarkerDecoder
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

		$applicationFactory = ApplicationFactory::getInstance();

		$parserData = $applicationFactory->newParserData(
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

		$conceptParserFunction->setPostProcHandler(
			$applicationFactory->create( 'PostProcHandler', $parser->getOutput() )
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

		$applicationFactory = ApplicationFactory::getInstance();

		$parserData = $applicationFactory->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$subobject = new Subobject( $parser->getTitle() );

		$messageFormatter = new MessageFormatter(
			$parser->getTargetLanguage()
		);

		$subobjectParserFunction = new SubobjectParserFunc(
			$parserData,
			$subobject,
			$messageFormatter
		);

		$subobjectParserFunction->isCapitalLinks(
			Site::isCapitalLinks()
		);

		$subobjectParserFunction->isComparableContent(
			$applicationFactory->getSettings()->get( 'smwgUseComparableContentHash' )
		);

		$stripMarkerDecoder = $applicationFactory->newMwCollaboratorFactory()->newStripMarkerDecoder(
			$parser->mStripState
		);

		$subobjectParserFunction->setStripMarkerDecoder(
			$stripMarkerDecoder
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

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$parserData = $applicationFactory->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$subobject = new Subobject( $parser->getTitle() );

		$messageFormatter = new MessageFormatter(
			$parser->getTargetLanguage()
		);

		$recurringEvents = new RecurringEvents();

		$recurringEvents->setDefaultNumRecurringEvents(
			$settings->get( 'smwgDefaultNumRecurringEvents' )
		);

		$recurringEvents->setMaxNumRecurringEvents(
			$settings->get( 'smwgMaxNumRecurringEvents' )
		);

		$recurringEventsParserFunction = new RecurringEventsParserFunc(
			$parserData,
			$subobject,
			$messageFormatter,
			$recurringEvents
		);

		$recurringEventsParserFunction->isCapitalLinks(
			Site::isCapitalLinks()
		);

		$recurringEventsParserFunction->isComparableContent(
			$settings->get( 'smwgUseComparableContentHash' )
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
	public function getAskParserFunctionDefinition() {

		$askParserFunctionDefinition = function( $parser ) {

			$applicationFactory = ApplicationFactory::getInstance();
			$settings = $applicationFactory->getSettings();

			$askParserFunction = $this->newAskParserFunction(
				$parser
			);

			if ( !$settings->get( 'smwgQEnabled' ) ) {
				return $settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_INL_ERROR ) ? $askParserFunction->isQueryDisabled(): '';
			}

			return $askParserFunction->parse( func_get_args() );
		};

		return [ 'ask', $askParserFunctionDefinition, 0 ];
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getShowParserFunctionDefinition() {

		$showParserFunctionDefinition = function( $parser ) {

			$applicationFactory = ApplicationFactory::getInstance();
			$settings = $applicationFactory->getSettings();

			$showParserFunction = $this->newShowParserFunction(
				$parser
			);

			if ( !$settings->get( 'smwgQEnabled' ) ) {
				return $settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_INL_ERROR ) ? $showParserFunction->isQueryDisabled(): '';
			}

			return $showParserFunction->parse( func_get_args() );
		};

		return [ 'show', $showParserFunctionDefinition, 0 ];
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getSubobjectParserFunctionDefinition() {

		$subobjectParserFunctionDefinition = function( $parser ) {

			$subobjectParserFunction = $this->newSubobjectParserFunction(
				$parser
			);

			return $subobjectParserFunction->parse(
				ParameterProcessorFactory::newFromArray( func_get_args() )
			);
		};

		return [ 'subobject', $subobjectParserFunctionDefinition, 0 ];
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getSetRecurringEventParserFunctionDefinition() {

		$recurringEventsParserFunctionDefinition = function( $parser ) {

			$recurringEventsParserFunction = $this->newRecurringEventsParserFunction(
				$parser
			);

			return $recurringEventsParserFunction->parse(
				ParameterProcessorFactory::newFromArray( func_get_args() )
			);
		};

		return [ 'set_recurring_event', $recurringEventsParserFunctionDefinition, 0 ];
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getSetParserFunctionDefinition() {

		$setParserFunctionDefinition = function( $parser ) {

			$setParserFunction = $this->newSetParserFunction(
				$parser
			);

			return $setParserFunction->parse(
				ParameterProcessorFactory::newFromArray( func_get_args() )
			);
		};

		return [ 'set', $setParserFunctionDefinition, 0 ];
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getConceptParserFunctionDefinition() {

		$conceptParserFunctionDefinition = function( $parser ) {

			$conceptParserFunction = $this->newConceptParserFunction(
				$parser
			);

			return $conceptParserFunction->parse( func_get_args() );
		};

		return [ 'concept', $conceptParserFunctionDefinition, 0 ];
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getDeclareParserFunctionDefinition() {

		$declareParserFunctionDefinition = function( $parser, $frame, $args ) {

			$declareParserFunction = $this->newDeclareParserFunction(
				$parser
			);

			return $declareParserFunction->parse( $frame, $args );
		};

		return [ 'declare', $declareParserFunctionDefinition, Parser::SFH_OBJECT_ARGS ];
	}

}
