<?php

namespace SMW\Test;

use SMW\LinksUpdateConstructed;

use ParserOutput;
use LinksUpdate;
use Title;

/**
 * Tests for the LinksUpdateConstructed class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\LinksUpdateConstructed
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class LinksUpdateConstructedTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\LinksUpdateConstructed';
	}

	/**
	 * Helper method that returns a LinksUpdateConstructed object
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 *
	 * @return LinksUpdateConstructed
	 */
	private function getInstance( Title $title = null ) {

		$title = $title === null ? $this->newTitle() : $title;
		$title->resetArticleID( rand( 1, 1000 ) );

		$parserOutput = new ParserOutput();
		$parserOutput->setTitleText( $title->getPrefixedText() );

		return new LinksUpdateConstructed( new LinksUpdate( $title, $parserOutput ) );
	}

	/**
	 * @test LinksUpdateConstructed::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test LinksUpdateConstructed::process
	 *
	 * @since 1.9
	 */
	public function testProcess() {

		$instance = $this->getInstance();
		$instance->setStore( $this->newMockObject()->getMockStore() );

		$this->assertTrue( $instance->process() );
	}

}
