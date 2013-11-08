<?php

namespace SMW\Test;

use SMW\ContentParser;

use Title;

/**
 * @covers \SMW\ContentParser
 * @covers \SMW\AskParserFunction
 * @covers \SMW\ShowParserFunction
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ParserFunctionIntegrationTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * @since 1.9
	 *
	 * @return ContentParser
	 */
	private function newContentParser( Title $title = null, $context = null ) {

		if( $title === null ) {
			$title = $this->newTitle();
		}

		return new ContentParser( $title );
	}

	/**
	 * @dataProvider textDataProvider
	 *
	 * Check that registered parser functions (especially those as closures) are
	 * generally executable during parsing of a standard text
	 *
	 * @since 1.9
	 */
	public function testParseFromText( $text ) {

		$instance = $this->newContentParser();
		$instance->parse( $text );

		$this->assertInstanceOf(
			'ParserOutput',
			$instance->getOutput(),
			'Asserts that a ParserOutput object is available'
			);

		$this->assertInternalType(
			'string',
			$instance->getOutput()->getText(),
			'Asserts that getText() is returning a string'
		);

	}

	/**
	 * @return array
	 */
	public function textDataProvider() {

		$provider = array();

		// #0 AskParserFunction
		$provider[] = array( $this->newRandomString() . '{{#ask: [[Modification date::+]]|limit=1}}' );

		// #1 ShowParserFunction
		$provider[] = array( $this->newRandomString() . '{{#show: [[Foo]]|limit=1}}' );

		// #2 SubobjectParserFunction
		$provider[] = array( $this->newRandomString() . '{{#subobject:|foo=bar|lila=lula,linda,luna|+sep=,}}' );

		// #3 RecurringEventsParserFunction
		// Test against 'Exception' with message 'Serialization of 'Closure' and Parser->braceSubstitution
		$provider[] = array( $this->newRandomString() . '{{#set_recurring_event:some more tests|property=has date|' .
			'has title=Some recurring title|title2|has group=Events123|Events456|start=June 8, 2010|end=June 8, 2011|' .
			'unit=week|period=1|limit=10|duration=7200|include=March 16, 2010;March 23, 2010|+sep=;|' .
			'exclude=March 15, 2010;March 22, 2010|+sep=;}}' );

		return $provider;
	}

}
