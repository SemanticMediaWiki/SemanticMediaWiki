<?php

namespace SMW\Test;

use SpecialPageFactory;
use RequestContext;
use SpecialPage;

/**
 * Tests for the special pages class
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
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * This class tests special pages to make sure they do not contain fatal errors
 *
 * Test is borrowed from the EducationProgram extension.
 *
 * @ingroup SMW
 * @ingroup Test
 */
class SpecialsTest extends SemanticMediaWikiTestCase {

	/**
	 * Helper method
	 *
	 * @return string
	 */
	public function getClass() {
		return false;
	}

	/**
	 * Provides special pages
	 *
	 * @return array
	 */
	public function specialProvider() {
		$specials = array(
			'SemanticStatistics'
		);

		$argLists = array();

		foreach ( $specials as $special ) {
			if ( array_key_exists( $special, $GLOBALS['wgSpecialPages'] ) ) {
				$specialPage = SpecialPageFactory::getPage( $special );
				$context = RequestContext::newExtraneousContext( $specialPage->getTitle() );

				$specialPage->setContext( clone $context );
				$argLists[] = array( clone $specialPage );

				$context->setUser( new MockSuperUser() );
				$specialPage->setContext( $context );
				$argLists[] = array( $specialPage );
			}
		}

		return $argLists;
	}

	/**
	 * @dataProvider specialProvider
	 *
	 * @param $specialPage
	 */
	public function testSpecial( SpecialPage $specialPage ) {
		try {
			$specialPage->execute( '' );
		}
		catch ( \Exception $exception ) {
			if ( !( $exception instanceof \PermissionsError ) && !( $exception instanceof \ErrorPageError ) ) {
				throw $exception;
			}
		}

		$this->assertTrue( true, 'SpecialPage was run without errors' );
	}
}