<?php

namespace SMW\Test;

use SMW\SpecialConcepts;
use SMW\DIWikiPage;
use SMWDataItem;

/**
 * Tests for the SpecialConcepts class
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
 *
 * @license GNU GPL v2+
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

		$this->assertTag( $matches, $this->getOutput() );
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
