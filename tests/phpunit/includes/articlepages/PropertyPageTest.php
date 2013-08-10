<?php

namespace SMW\Test;

use SMWPropertyPage;

/**
 * Tests for the SMWPropertyPage class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMWPropertyPage
 *
 * @ingroup Pages
 *
 * @group SMW
 * @group SMWExtension
 */
class PropertyPageTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWPropertyPage';
	}

	/**
	 * Helper method that returns a SMWPropertyPage object
	 *
	 * @return SMWPropertyPage
	 */
	private function getInstance() {
		return new SMWPropertyPage( $this->newTitle() );
	}

	/**
	 * @test SMWPropertyPage::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

}
