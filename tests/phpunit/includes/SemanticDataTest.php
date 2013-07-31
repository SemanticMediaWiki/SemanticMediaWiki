<?php

namespace SMW\Test;

use SMW\SemanticData;

/**
 * Tests for the SemanticData class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SemanticData
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SemanticDataTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SemanticData';
	}

	/**
	 * Helper method that returns a SemanticData object
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	private function getInstance() {
		return new SemanticData( $this->getSubject() );
	}

	/**
	 * @test SemanticData::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
		$this->assertInstanceOf( 'SMWSemanticData', $this->getInstance() );
	}

}
