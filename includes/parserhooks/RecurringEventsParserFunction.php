<?php

namespace SMW;

use Parser;
use SMWParseData;

/**
 * Class handling #set_recurring_event parser function
 *
 * @see http://semantic-mediawiki.org/wiki/Help:Recurring_events
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
 * @file
 * @since 1.9
 *
 * @ingroup SMW
 * @ingroup ParserHooks
 *
 * @author mwjames
 */
class RecurringEventsHandler {

	/**
	 * Method for handling the set_recurring_event parser function.
	 *
	 * @since 1.9
	 *
	 * @param Parser $parser
	 */
	public static function render( Parser &$parser ) {
		$params = func_get_args();
		array_shift( $params );

		$mainSemanticData = SMWParseData::getSMWData( $parser );
		$subject = $mainSemanticData->getSubject();

		// FIXME Use a class instance
		$parameters = ParserParameterFormatter::singleton()->getParameters( $params );

		// Get recurring events
		$events = new RecurringEvents( $parameters );
		$errors = $events->getErrors();

		// Go over available recurring events
		foreach ( $events->getDates() as $date_str ) {

			// Override parameters as only unused parameters are necessary
			$parameters = $events->getParameters();

			// Add individual date string as parameter in order to be handled
			// equally by the SubobjectHandler as member of the same instance
			// once the ParserParameter class is fixed use a
			// $parameters->addParameter( key, value ) instead
			$parameters[$events->getProperty()][] = $date_str;

			// Instantiate subobject handler for each new date
			$handler = new SubobjectHandler( $subject, $parameters );

			// Store an individual subobject
			SMWParseData::getSMWData( $parser )->addPropertyObjectValue(
				$handler->getSubobject()->getProperty(),
				$handler->getSubobject()->getContainer()
			);

			// Collect individual errors
			$errors = array_merge( $handler->getSubobject()->getErrors(), $errors );
		}

		// See comments in SubobjectHandler class
		return smwfEncodeMessages( $errors , 'warning', '' , false );
	}
}
