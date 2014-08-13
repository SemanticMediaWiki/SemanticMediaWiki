<?php

namespace SMW\Test;

use SMW\ConceptPage;

/**
 * Tests for the ConceptPage class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ConceptPage
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class ConceptPageTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ConceptPage';
	}

	/**
	 * Helper method that returns a ConceptPage object
	 *
	 * @return ConceptPage
	 */
	private function getInstance() {
		return new ConceptPage( $this->newTitle() );
	}

	/**
	 * @test ConceptPage::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

}
