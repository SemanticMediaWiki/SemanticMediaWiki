<?php

namespace SMW\Test;

use SMWDataValueFactory;
use SMWDataItem;

/**
 * Tests for the SMWDataValueFactory class
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

/**
 * Tests for the SMWDataValueFactory class
 *
 * @ingroup SMW
 * @ingroup Test
 */
class DataValueFactoryTest extends \MediaWikiTestCase {

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getPropertyValueDataProvider() {
		return array(
			array( 'Foo'  , 'Bar'          , 'Bar'          , 'SMWDataValue' ),
			array( 'Foo'  , 'Bar[[ Foo ]]' , 'Bar[[ Foo ]]' , 'SMWDataValue' ),
			array( 'Foo'  , '9001'         , '9001'         , 'SMWDataValue' ),
			array( 'Foo'  , 1001           , '1001'         , 'SMWDataValue' ),
			array( 'Foo'  , '-%&$*'        , '-%&$*'        , 'SMWDataValue' ),
			array( 'Foo'  , '_Bar'         , 'Bar'          , 'SMWDataValue' ),
			array( 'Foo'  , 'bar'          , 'Bar'          , 'SMWDataValue' ),
			array( '-Foo' , 'Bar'          , ''             , 'SMWErrorValue' ),
			array( '_Foo' , 'Bar'          , ''             , 'SMWPropertyValue' ),
		);
	}

	/**
	 * @dataProvider getPropertyValueDataProvider
	 *
	 * @see SMWDataValueFactory::addPropertyValue
	 * @since 1.9
	 *
	 * @param $propertyName
	 * @param $value
	 * @param $expectedValue
	 * @param $expectedInstance
	 */
	public function testAddPropertyValue( $propertyName, $value, $expectedValue, $expectedInstance ) {
		$dataValue = SMWDataValueFactory::newPropertyValue( $propertyName, $value );

		// Check the returned instance
		$this->assertInstanceOf( $expectedInstance , $dataValue );

		if ( $dataValue->getErrors() === array() ){
			$this->assertInstanceOf( 'SMWDIProperty', $dataValue->getProperty() );
			$this->assertContains( $propertyName, $dataValue->getProperty()->getLabel() );
			if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_WIKIPAGE ){
				$this->assertEquals( $expectedValue, $dataValue->getWikiValue() );
			}
		} else {
			$this->assertInternalType( 'array', $dataValue->getErrors() );
		}
	}
}
