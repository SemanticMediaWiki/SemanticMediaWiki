<?php

namespace SMW;

use Parser;

/**
 * Class that provides the {{#set_recurring_event}} parser function
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @see http://semantic-mediawiki.org/wiki/Help:Recurring_events
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup ParserFunction
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Class that provides the {{#set_recurring_event}} parser function
 *
 * RecurringEventsParserFunction is an extension of the SubobjectParserFunction,
 * inheritance (instead of composition) is used to get access to internal
 * methods only relevant to SubobjectParserFunction.
 *
 * @ingroup SMW
 * @ingroup ParserFunction
 */
class RecurringEventsParserFunction extends SubobjectParserFunction {

	/**
	 * Returns necessary Settings object
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public function getSettings() {
		return Settings::newFromArray( array(
			'smwgDefaultNumRecurringEvents' => $GLOBALS['smwgDefaultNumRecurringEvents'],
			'smwgMaxNumRecurringEvents' => $GLOBALS['smwgMaxNumRecurringEvents'] )
		);
	}

	/**
	 * Parse parameters, and update the ParserOutput with data from the
	 * RecurringEvents object
	 *
	 * @since 1.9
	 *
	 * @param ArrayFormatter $parameters
	 *
	 * @return string|null
	 */
	public function parse( ArrayFormatter $parameters ) {

		// Get recurring events
		$events = new RecurringEvents( $parameters->toArray(), $this->getSettings() );
		$this->msgFormatter->addFromArray( $events->getErrors() );

		foreach ( $events->getDates() as $date_str ) {

			// Override existing parameters array with the returned
			// parameters array from recurring events, its holds all
			// unprocessed parameters needed for further processing
			$parameters->setParameters( $events->getParameters() );

			// Add the date string as individual property / value parameter
			$parameters->addParameter( $events->getProperty(), $date_str );

			// getFirst() indicates if an event should be directly linked to
			// the page that embeds the parser call and if so use this value as
			// user property together with the embedding page as property value
			if ( $parameters->getFirst() !== null ) {
				$parameters->addParameter(
					$parameters->getFirst(),
					$this->parserData->getTitle()->getPrefixedText()
				);
			}

			// Register object values to the subobject as anonymous entity
			// which changes with the set of parameters available
			// @see SubobjectParserFunction::addSubobjectValues
			$this->addSubobjectValues( $parameters->toArray() );

			// Add subobject container to the semantic data object
			// Each previous $parameters->toArray() call will produce a unique
			// subobject that is now added to the semantic data instance
			$this->parserData->getData()->addPropertyObjectValue(
				$this->subobject->getProperty(),
				$this->subobject->getContainer()
			);

			// Collect errors that occurred during processing
			$this->msgFormatter->addFromArray( $this->subobject->getErrors() );
		}

		// Update ParserOutput
		$this->parserData->updateOutput();

		return $this->msgFormatter->getHtml();
	}

	/**
	 * Parser::setFunctionHook {{#set_recurring_event}} handler method
	 *
	 * @param Parser $parser
	 *
	 * @return string|null
	 */
	public static function render( Parser &$parser ) {
		$instance = new self(
			new ParserData( $parser->getTitle(), $parser->getOutput() ),
			new Subobject( $parser->getTitle() ),
			new MessageFormatter( $parser->getTargetLanguage() )
		);
		return $instance->parse( new ParserParameterFormatter( func_get_args() ) );
	}
}
