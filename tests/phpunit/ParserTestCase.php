<?php

namespace SMW\Test;

use SMW\ParserParameterFormatter;
use SMW\ParserTextProcessor;
use SMW\ParserData;
use SMW\Settings;

use ParserOutput;
use Title;
use User;
use WikiPage;
use Parser;

/**
 * Class contains methods to access data in connection with the Parser or
 * ParserOutput object
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
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup SMWParser
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Class contains methods to access data in connection with the Parser or
 * ParserOutput object
 *
 * @ingroup SMW
 */
abstract class ParserTestCase extends SemanticMediaWikiTestCase {

	/**
	 * Helper method to create Parser object
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param User $user
	 *
	 * @return Parser
	 */
	protected function getParser( Title $title, User $user) {
		$wikiPage = new WikiPage( $title );
		$parserOptions = $wikiPage->makeParserOptions( $user );

		$parser = new Parser( $GLOBALS['wgParserConf'] );
		$parser->setTitle( $title );
		$parser->setUser( $user );
		$parser->Options( $parserOptions );
		$parser->clearState();
		return $parser;
	}

	/**
	 * Helper method that returns a ParserOutput object
	 *
	 * @return ParserOutput
	 */
	protected function getParserOutput() {
		return new ParserOutput();
	}

	/**
	 * Helper method that returns a ParserData object
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 * @param array $settings
	 *
	 * @return ParserData
	 */
	protected function getParserData( Title $title, ParserOutput $parserOutput, array $settings = array() ) {
		return new ParserData( $title, $parserOutput, $settings );
	}

	/**
	 * Helper method that returns a ParserTextProcessor object
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 * @param Settings $settings
	 *
	 * @return ParserTextProcessor
	 */
	protected function getParserTextProcessor( Title $title, ParserOutput $parserOutput, Settings $settings ) {
		return new ParserTextProcessor(
			$this->getParserData( $title, $parserOutput ),
			$settings
		);
	}

	/**
	 * Helper method that returns a ParserParameterFormatter object
	 *
	 * @param array $params
	 *
	 * @return ParserParameterFormatter
	 */
	protected function getParserParameterFormatter( array $params ) {
		return new ParserParameterFormatter( $params );
	}
}
