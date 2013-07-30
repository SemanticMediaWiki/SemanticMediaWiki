<?php

namespace SMW\Test;

use SMW\SpecialConcepts;
use SMW\DIWikiPage;
use SMWDataItem;

/**
 * Tests for the SpecialConcepts class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers SMW\SpecialConcepts
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SpecialConceptsTest extends SpecialPageTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SpecialConcepts';
	}

	/**
	 * Helper method that returns a SpecialConcepts object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return SpecialConcepts
	 */
	protected function getInstance() {
		return new SpecialConcepts();
	}

	/**
	 * @test SpecialConcepts::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test SpecialConcepts::execute
	 *
	 * @since 1.9
	 */
	public function testExecute() {

		$this->getInstance();
		$this->execute();

		$matches = array(
			'tag' => 'span',
			'attributes' => array( 'class' => 'smw-sp-concept-docu' )
		);

		$this->assertTag( $matches, $this->getText() );
	}

	/**
	 * @test SpecialConcepts::getHtml
	 *
	 * @since 1.9
	 */
	public function testGetHtml() {

		$instance = $this->getInstance();

		$matches = array(
			'tag' => 'span',
			'attributes' => array( 'class' => 'smw-sp-concept-empty' )
		);
		$this->assertTag( $matches, $instance->getHtml( array(), 0, 0, 0 ) );

		$matches = array(
			'tag' => 'span',
			'attributes' => array( 'class' => 'smw-sp-concept-count' )
		);
		$this->assertTag( $matches, $instance->getHtml( array( $this->getSubject() ), 1, 1, 1 ) );

	}
}
