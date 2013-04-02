<?php

namespace SMW\Tests;

/**
 * Tests for the SMW\DIConcept class
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
 * @group SMWDataItems
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class DIConceptTest extends DataItemTest {

	/**
	 * @see DataItemTest::getClass
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return 'SMW\DIConcept';
	}

	/**
	 * @see DataItemTest::constructorProvider
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function constructorProvider() {
		return array(
			array( true, 'Foo', '', '', '', '' ),
			array( false, 'Bar' ),
		);
	}

	/**
	 * Data provider for testing concept cache setter/getter
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function conceptCacheDataProvider() {
		return array(
			array( 'empty', '', '' ),
			array( 'full', '1358515326', '1000' ),
		);
	}

	/**
	 * Test concept cache setter/getter
	 *
	 * @since 1.9
	 *
	 * @dataProvider conceptCacheDataProvider
	 */
	public function testConceptCacheSetterGetter( $status, $date, $count ) {
		$reflector = new \ReflectionClass( $this->getClass() );
		$instance = $reflector->newInstanceArgs( array ( 'Foo', '', '', '', '' ) );

		$instance->setCacheStatus( $status );
		$instance->setCacheDate( $date ) ;
		$instance->setCacheCount( $count );

		$this->assertEquals( $status, $instance->getCacheStatus() );
		$this->assertEquals( $date, $instance->getCacheDate() );
		$this->assertEquals( $count, $instance->getCacheCount() );
	}
}