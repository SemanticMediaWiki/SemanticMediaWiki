<?php

namespace SMW;

/**
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

	/** @var Settings */
	private $settings;

	/** @var RecurringEvents */
	private $events;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param Subobject $subobject
	 * @param MessageFormatter $messageFormatter
	 * @param Settings $settings
	 */
	public function __construct(
		ParserData $parserData,
		Subobject $subobject,
		MessageFormatter $messageFormatter,
		Settings $settings
	) {
		parent::__construct ( $parserData, $subobject, $messageFormatter );
		$this->settings = $settings;
	}

	/**
	 * Parse parameters, and update the ParserOutput with data from the
	 * RecurringEvents object
	 *
	 * @since 1.9
	 *
	 * @param ParserParameterProcessor $parameters
	 *
	 * @return string|null
	 */
	public function parse( ParserParameterProcessor $parameters ) {

		$this->setFirstElementForPropertyLabel( true );

		// Get recurring events
		$this->events = new RecurringEvents( $parameters->toArray(), $this->settings );
		$this->messageFormatter->addFromArray( $this->events->getErrors() );

		foreach ( $this->events->getDates() as $date_str ) {

			// Override existing parameters array with the returned
			// pre-processed parameters array from recurring events
			$parameters->setParameters( $this->events->getParameters() );

			// Add the date string as individual property / value parameter
			$parameters->addParameter(
				$this->events->getProperty(),
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

		return $this->messageFormatter
			->addFromArray( $this->parserData->getErrors() )
			->getHtml();
	}

}
