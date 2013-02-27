<?php

namespace SMW\Test;
use SMW\Subobject;

/**
 * Tests for the SMW\Subobject class.
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
 * @file
 * @since 1.9
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class SubobjectTest extends \MediaWikiTestCase {

	/**
	 * Helper method to get title object
	 *
	 */
	private function getSubject( $name ){
		$title = \Title::newFromText( $name );
		return \SMWDIWikiPage::newFromTitle( $title );
	}

	/**
	 * Helper method to get subobject
	 *
	 */
	private function setSubobject( $subject, $name ){
		return new Subobject( $this->getSubject( $subject ), $name );
	}

	/**
	 * Test instance
	 *
	 */
	public function testInstance() {
		$subject = $this->getSubject( 'Foo' );

		$instance = new Subobject( $subject );
		$this->assertInstanceOf( 'SMW\Subobject', $instance );
	}

	/**
	 * Test setSemanticData()
	 *
	 */
	public function testSetSemanticData() {
		$subject = $this->getSubject( 'Foo' );
		$subobject = new Subobject( $subject );

		$instance = $subobject->setSemanticData( 'Bar' );
		$this->assertInstanceOf( '\SMWContainerSemanticData', $instance );
	}

	/**
	 * Test getName()
	 *
	 */
	public function testGetName() {
		$subobject = $this->setSubobject( 'Foo' , 'Bar' );

		$name = $subobject->getName();
		$this->assertEquals( $name, 'Bar' );
	}

	/**
	 * Test getProperty()
	 *
	 */
	public function testGetProperty() {
		$subobject = $this->setSubobject( 'Foo' , 'Bar' );

		$instance = $subobject->getProperty();
		$this->assertInstanceOf( '\SMWDIProperty', $instance );
	}


	/**
	 * Test addPropertyValue()
	 *
	 */
	public function testAddPropertyValue() {
		$subobject = $this->setSubobject( 'Foo' , 'Bar' );

		$subobject->addPropertyValue( 'Foo', 'bar' );
		$subobject->addPropertyValue( 'Bar', 'foo' );

		$errors = $subobject->getErrors();
		$this->assertTrue( is_array( $errors ) );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test getContainer()
	 *
	 */
	public function testGetContainer() {
		$subobject = $this->setSubobject( 'Foo' , 'Bar' );

		$subobject->addPropertyValue( 'Foo', 'bar' );
		$subobject->addPropertyValue( 'Bar', 'foo' );

		$instance = $subobject->getContainer();
		$this->assertInstanceOf( '\SMWDIContainer', $instance );
	}
}