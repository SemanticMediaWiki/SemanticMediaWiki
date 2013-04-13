<?php

namespace SMW\Test;

use SMW\Subobject;
use SMWDIWikiPage;
use SMWDataItem;
use SMWDataValueFactory;
use Title;

/**
 * Tests for the SMW\Subobject class
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
	 * DataProvider
	 *
	 * @return array
	 */
	public function getDataProvider() {
		$diPropertyError = new \SMWDIProperty( \SMWDIProperty::TYPE_ERROR );
		return array(
			array(
				// #0
				'Foo', // Test data
				array(
					'identifier' => 'Bar',
					'property' => array( 'Foo' => 'bar' )
				),
				array( // Expected results
					'name' => 'Bar',
					'errors' => 0,
					'propertyLabel' => 'Foo',
					'propertyValue' => 'Bar'
				)
			),

			// #1
			array(
				'Foo',
				array(
					'identifier' => 'bar',
					'property' => array( 'FooBar' => 'bar Foo' )
				),
				array( // Expected results
					'name' => 'bar',
					'errors' => 0,
					'propertyLabel' => 'FooBar',
					'propertyValue' => 'Bar Foo',
				)
			),

			// #2
			array(
				'Bar',
				array(
					'identifier' => 'foo',
					'property' => array( 9001 => 1001 )
				),
				array( // Expected results
					'name' => 'foo',
					'errors' => 0,
					'propertyLabel' => array( 9001 ),
					'propertyValue' => array( 1001 ),
				)
			),

			// #3
			array(
				'Bar foo',
				array(
					'identifier' => 'foo bar',
					'property' => array( 1001 => 9001, 'Foo' => 'Bar' )
				),
				array( // Expected results
					'name' => 'foo bar',
					'errors' => 0,
					'propertyLabel' => array( 1001, 'Foo' ),
					'propertyValue' => array( 9001, 'Bar' ),
				)
			),

			// #4 Property with leading underscore will raise error
			array(
				'Foo',
				array(
					'identifier' => 'bar',
					'property' => array( '_FooBar' => 'bar Foo' )
				),
				array( // Expected results
					'name' => 'bar',
					'errors' => 1,
					'propertyLabel' => 'FooBar',
					'propertyValue' => 'Bar Foo',
				)
			),

			// #5 Inverse property will raise error
			array(
				'Foo',
				array(
					'identifier' => 'bar',
					'property' => array( '-FooBar' => 'bar Foo' )
				),
				array( // Expected results
					'name' => 'bar',
					'errors' => 1,
					'propertyLabel' => 'FooBar',
					'propertyValue' => 'Bar Foo',
				)
			),

			// #6 Improper value for wikipage property will add an 'Has improper value for'
			array(
				'Bar',
				array(
					'identifier' => 'bar',
					'property' => array( 'Foo' => '' )
				),
				array( // Expected results
					'name' => 'bar',
					'errors' => 1,
					'propertyLabel' => array( $diPropertyError->getLabel(), 'Foo' ),
					'propertyValue' => 'Foo',
				)
			),
		);
	}

	/**
	 * Helper method to get subobject
	 *
	 */
	private function getSubobject( $title, $name = '' ){
		return new Subobject( Title::newFromText( $title ), $name );
	}

	/**
	 * Test constructor
	 *
	 * @dataProvider getDataProvider
	 */
	public function testConstructor( $title ) {
		$instance = new Subobject( Title::newFromText( $title ) );
		$this->assertInstanceOf( 'SMW\Subobject', $instance );
	}

	/**
	 * Test setSemanticData()
	 *
	 * @dataProvider getDataProvider
	 */
	public function testSetSemanticData( $title, array $setup ) {
		$subobject = new Subobject( Title::newFromText( $title ) );

		$instance = $subobject->setSemanticData( $setup['identifier'] );
		$this->assertInstanceOf( '\SMWContainerSemanticData', $instance );
	}

	/**
	 * Test getName()
	 *
	 * @dataProvider getDataProvider
	 */
	public function testGetName( $title, array $setup, array $expected ) {
		$subobject = $this->getSubobject( $title , $setup['identifier'] );
		$this->assertEquals( $expected['name'], $subobject->getName() );
	}

	/**
	 * Test getProperty()
	 *
	 * @dataProvider getDataProvider
	 */
	public function testGetProperty( $title, array $setup ) {
		$subobject = $this->getSubobject( $title, $setup['identifier'] );
		$this->assertInstanceOf( '\SMWDIProperty', $subobject->getProperty() );
	}

	/**
	 * Test addPropertyValueString() exception
	 *
	 * @dataProvider getDataProvider
	 */
	public function testAddPropertyValueStringException( $title, array $setup ) {
		$this->setExpectedException( 'MWException' );
		$subobject = $this->getSubobject( $title );

		foreach ( $setup['property'] as $property => $value ){
			$subobject->addPropertyValue( $property, $value );
		}
	}

	/**
	 * Test addPropertyValue()
	 *
	 * @dataProvider getDataProvider
	 */
	public function testAddPropertyValue( $title, array $setup, array $expected ) {
		$subobject = $this->getSubobject( $title, $setup['identifier'] );

		foreach ( $setup['property'] as $property => $value ){
			$subobject->addPropertyValue( $property, $value );
		}

		// Check errors
		$this->assertCount( $expected['errors'], $subobject->getErrors() );

		// Check added property
		foreach ( $subobject->getSemanticData()->getProperties() as $key => $diproperty ){
			$this->assertInstanceOf( 'SMWDIProperty', $diproperty );
			$this->assertContains( $diproperty->getLabel(), $expected['propertyLabel'] );

			// Check added property value
			foreach ( $subobject->getSemanticData()->getPropertyValues( $diproperty ) as $key => $dataItem ){
				$this->assertInstanceOf( 'SMWDataItem', $dataItem );
				$dataValue = SMWDataValueFactory::newDataItemValue( $dataItem, $diproperty );
				if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_WIKIPAGE ){
					$this->assertContains( $dataValue->getWikiValue(), $expected['propertyValue'] );
				}
			}
		}
	}

	/**
	 * Test getAnonymousIdentifier()
	 *
	 * @dataProvider getDataProvider
	 */
	public function testGetAnonymousIdentifier( $title, array $setup ) {
		$subobject = $this->getSubobject( $title );
		// Looking for the _ instead of comparing the hash key as it
		// can change with the method applied (md4, sha1 etc.)
		$this->assertContains( '_', $subobject->getAnonymousIdentifier( $setup['identifier'] ) );
	}

	/**
	 * Test getContainer()
	 *
	 * @dataProvider getDataProvider
	 */
	public function testGetContainer( $title, array $setup ) {
		$subobject = $this->getSubobject( $title, $setup['identifier'] );
		$this->assertInstanceOf( '\SMWDIContainer', $subobject->getContainer() );
	}
}
