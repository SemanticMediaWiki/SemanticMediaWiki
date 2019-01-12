<?php

namespace SMW\ParserFunctions;

use SMW\MessageFormatter;
use SMW\ParserData;
use SMW\ParserParameterProcessor;
use SMW\RecurringEvents;
use SMW\Subobject;

/**
 * @private This class should not be instantiated directly, please use
 * ParserFunctionFactory::newRecurringEventsParserFunction
 *
 * Class that provides the {{#set_recurring_event}} parser function
 *
 * @see http://semantic-mediawiki.org/wiki/Help:Recurring_events
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RecurringEventsParserFunction extends SubobjectParserFunction {

	/**
	 * @var RecurringEvents
	 */
	private $recurringEvents;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param Subobject $subobject
	 * @param MessageFormatter $messageFormatter
	 * @param RecurringEvents $recurringEvents
	 */
	public function __construct( ParserData $parserData, Subobject $subobject, MessageFormatter $messageFormatter, RecurringEvents $recurringEvents ) {
		parent::__construct ( $parserData, $subobject, $messageFormatter );
		$this->recurringEvents = $recurringEvents;
	}

	/**
	 * @since 1.9
	 *
	 * @param ParserParameterProcessor $parameters
	 *
	 * @return string|null
	 */
	public function parse( ParserParameterProcessor $parameters ) {

		$this->useFirstElementAsPropertyLabel( true );

		$this->recurringEvents->parse(
			$parameters->toArray()
		);

		$this->messageFormatter->addFromArray(
			$this->recurringEvents->getErrors()
		);

		foreach ( $this->recurringEvents->getDates() as $date_str ) {

			// Override existing parameters array with the returned
			// pre-processed parameters array from recurring events
			$parameters->setParameters( $this->recurringEvents->getParameters() );

			// Add the date string as individual property / value parameter
			$parameters->addParameter(
				$this->recurringEvents->getProperty(),
				$date_str
			);

			// @see SubobjectParserFunction::addDataValuesToSubobject
			// Each new $parameters set will add an additional subobject
			// to the instance
			if ( $this->addDataValuesToSubobject( $parameters ) ) {
				$this->parserData->getSemanticData()->addSubobject( $this->subobject );
			}

			// Collect errors that occurred during processing
			$this->messageFormatter->addFromArray( $this->subobject->getErrors() );
		}

		// Update ParserOutput
		$this->parserData->pushSemanticDataToParserOutput();

		$this->messageFormatter->addFromArray(
			$this->parserData->getErrors()
		);

		return $this->messageFormatter->getHtml();
	}

}
