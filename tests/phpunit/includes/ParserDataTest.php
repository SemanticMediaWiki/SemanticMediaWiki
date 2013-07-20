<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\ParserData;
use SMW\Settings;

use ParserOutput;
use Title;

/**
 * Tests for the ParserData class
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
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ParserData
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ParserDataTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ParserData';
	}

	/**
	 * Helper method that returns a ParserData object
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 * @param array $settings
	 *
	 * @return ParserData
	 */
	private function getInstance( Title $title, ParserOutput $parserOutput, array $settings = array() ) {
		return new ParserData(
			$title,
			$parserOutput,
			$settings
		);
	}

	/**
	 * @test ParserData::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance(
			$this->getTitle(),
			$this->getParserOutput()
		);
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * Provides an array specified by property, value, errorCount, propertyCount
	 *
	 * @return array
	 */
	public function getPropertyValueDataProvider() {
		return array(
			array( 'Foo'  , 'Bar', 0, 1 ),
			array( '-Foo' , 'Bar', 1, 0 ),
			array( '_Foo' , 'Bar', 1, 0 ),
		);
	}

	/**
	 * @test ParserData::addPropertyValue
	 * @dataProvider getPropertyValueDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $propertyName
	 * @param $value
	 * @param $errorCount
	 * @param $propertyCount
	 */
	public function testAddPropertyValue( $propertyName, $value, $errorCount, $propertyCount ) {
		$instance = $this->getInstance(
			$this->getTitle(),
			$this->getParserOutput()
		);

		// Values
		$instance->addPropertyValue(
			DataValueFactory::newPropertyValue(
				$propertyName,
				$value
			)
		);

		// Check the returned instance
		if ( $errorCount === 0 ){
			$expected['propertyCount'] = $propertyCount;
			$expected['propertyLabel'] = $propertyName;
			$expected['propertyValue'] = $value;
			$this->assertInstanceOf( 'SMWSemanticData', $instance->getData() );
			$this->assertSemanticData( $instance->getData(), $expected );
		} else {
			$this->assertCount( $errorCount, $instance->getErrors() );
		}
	}

}
