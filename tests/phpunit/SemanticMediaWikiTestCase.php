<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\Settings;

use Title;

use SMWDIWikiPage;
use SMWSemanticData;
use SMWDataItem;

/**
 * Class contains general purpose methods
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
 * @ingroup SMW
 * @ingroup SMWParser
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Class contains general purpose methods
 *
 * @group SMW
 * @group SMWExtension
 */
abstract class SemanticMediaWikiTestCase extends \PHPUnit_Framework_TestCase {

	/**
	 * Returns the name of the deriving class being tested
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected abstract function getClass();

	/**
	 * Helper method that returns a randomized Title object to avoid results
	 * are influenced by cross instantiated objects with the same title name
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 *
	 * @return Title
	 */
	protected function getTitle( $namespace = NS_MAIN ) {
		return Title::newFromText( $this->getRandomString(), $namespace );
	}

	/**
	 * Helper method that returns a User object
	 *
	 * @since 1.9
	 *
	 * @return User
	 */
	protected function getUser() {
		return new MockSuperUser();
	}

	/**
	 * Helper method that returns a randomized SMWDIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 *
	 * @return SMWDIWikiPage
	 */
	protected function getSubject( $namespace = NS_MAIN ) {
		return SMWDIWikiPage::newFromTitle( $this->getTitle( $namespace ) );
	}

	/**
	 * Helper method that returns a Settings object
	 *
	 * @since 1.9
	 *
	 * @param array $settings
	 *
	 * @return Settings
	 */
	protected function getSettings( array $settings = array() ) {
		return Settings::newFromArray( $settings );
	}

	/**
	 * Helper method that returns a random string
	 *
	 * @since 1.9
	 *
	 * @param $length
	 *
	 * @return string
	 */
	protected function getRandomString( $length = 10 ) {
		return substr( str_shuffle( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ), 0, $length );
	}

	/**
	 * Asserts that for a given semantic container expected property / value
	 * pairs are available
	 *
	 * Expected assertion array should follow
	 * 'propertyCount' => int
	 * 'propertyLabel' => array() or 'propertyKey' => array()
	 * 'propertyValue' => array()
	 *
	 * @param SMWSemanticData $semanticData
	 * @param array $expected
	 */
	protected function assertSemanticData( SMWSemanticData $semanticData, array $expected ) {
		$this->assertCount( $expected['propertyCount'], $semanticData->getProperties() );

		// Assert expected properties
		foreach ( $semanticData->getProperties() as $key => $diproperty ) {
			$this->assertInstanceOf( 'SMWDIProperty', $diproperty );

			if ( isset( $expected['propertyKey']) ){
				$this->assertContains( $diproperty->getKey(), $expected['propertyKey'] );
			} else {
				$this->assertContains( $diproperty->getLabel(), $expected['propertyLabel'] );
			}

			// Assert property values
			foreach ( $semanticData->getPropertyValues( $diproperty ) as $dataItem ){
				$dataValue = DataValueFactory::newDataItemValue( $dataItem, $diproperty );
				$DItype = $dataValue->getDataItem()->getDIType();

				if ( $DItype === SMWDataItem::TYPE_WIKIPAGE ){
					$this->assertContains( $dataValue->getWikiValue(), $expected['propertyValue'] );
				} else if ( $DItype === SMWDataItem::TYPE_NUMBER ){
					$this->assertContains( $dataValue->getNumber(), $expected['propertyValue'] );
				} else if ( $DItype === SMWDataItem::TYPE_BLOB ){
					$this->assertContains( $dataValue->getWikiValue(), $expected['propertyValue'] );
				}

			}
		}
	}
}
