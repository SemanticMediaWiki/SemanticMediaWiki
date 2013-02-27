<?php

namespace SMW\Test;

use SMW\SubobjectHandler;
use SMW\Subobject;
use SMW\ParserParameter;

use SMWDIWikiPage;
use Title;
use MWException;

/**
 * Tests for the SMW\SubobjectHandler class.
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
class SubobjectHandlerTest extends \MediaWikiTestCase {

	/**
	 * DataProvider
	 *
	 */
	public function getParametersDataProvider() {
		return array(
			array(
				array( 'Foo=bar', 'Bar=foo' ),
				array( 'errors' => 0 , 'DBkey' => 'Foo' )
				),
			);
	}

	/**
	 * Helper method to get title object
	 *
	 */
	private function getSubject( $name ){
		$title = Title::newFromText( $name );
		return SMWDIWikiPage::newFromTitle( $title );
	}

	/**
	 * Helper method
	 *
	 */
	private function getInstance( array $params, $DBKey ) {
		$subject = $this->getSubject( $DBKey );

		// FIXME Class instance
		$parameters = ParserParameter::singleton()->getParameters( $params );
		$instance = new SubobjectHandler( $subject, $parameters );
		return $instance;
	}

	/**
	 * Test instance
	 *
	 * @expectedException MWException
	 * @dataProvider getParametersDataProvider
	 */
	public function testInstance( array $params, array $expected ) {
		$subject = $this->getSubject( $expected['DBkey'] );

		// Raises an exception
		$instance = new SubobjectHandler( $subject );
		$this->assertInstanceOf( 'SMW\SubobjectHandler', $instance );

		$instance = new SubobjectHandler( $subject, $params );
		$this->assertInstanceOf( 'SMW\SubobjectHandler', $instance );
	}

	/**
	 * Test getSubobject()
	 *
	 * @dataProvider getParametersDataProvider
	 */
	public function testGetSubobject( array $params, array $expected ) {
		$instance = $this->getInstance( $params, $expected['DBkey'] );

		$this->assertInstanceOf( 'SMW\Subobject', $instance->getSubobject() );

		// Check available property instance
		$this->assertInstanceOf( '\SMWDIProperty', $instance->getSubobject()->getProperty() );

		// Check for errors
		$this->assertEquals( count( $instance->getSubobject()->getErrors() ), $expected['errors'] );

		// There is no easy way to verify the DIContainer therefore we use the subject as
		// comparison object to check accessibility of an instantiated object
		$this->assertEquals(
			$instance->getSubobject()->getContainer()->getSemanticData()->getSubject()->getDBkey(),
			$this->getSubject( $expected['DBkey'] )->getDBkey()
		);

		// Other internal subobject tested are done in the SubobjectTest class

	}
}