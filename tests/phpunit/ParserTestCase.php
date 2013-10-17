<?php

namespace SMW\Test;

use SMW\ParserParameterFormatter;
use SMW\ContentProcessor;
use SMW\ParserData;
use SMW\Settings;
use SMW\EmptyContext;

use ParserOutput;
use Title;
use User;
use WikiPage;
use Parser;

/**
 * Access methods in connection with the Parser or ParserOutput object
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Access methods in connection with the Parser or ParserOutput object
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
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
	protected function getParser( Title $title, User $user ) {
		return $this->newParser( $title, $user );
	}

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
	protected function newParser( Title $title, User $user ) {
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
		return $this->newParserOutput();
	}

	/**
	 * Helper method that returns a ParserOutput object
	 *
	 * @return ParserOutput
	 */
	protected function newParserOutput() {
		return new ParserOutput();
	}

	/**
	 * Helper method that returns a ParserData object
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return ParserData
	 */
	protected function newParserData( Title $title, ParserOutput $parserOutput ) {
		return new ParserData( $title, $parserOutput );
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

		$context = new EmptyContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		$parserData = $this->newParserData( $title, $parserOutput );

		return new ContentProcessor( $parserData, $context );
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
