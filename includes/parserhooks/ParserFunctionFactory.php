<?php

namespace SMW;

use Parser;

/**
 * Factory class for convenience parser function instantiation
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
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
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
