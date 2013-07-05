<?php

namespace SMW\Test;

use SpecialPageFactory;
use RequestContext;
use FauxRequest;
use SpecialPage;
use Language;

/**
 * Tests for registered special pages
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */

/**
 * @covers SMWSpecialWantedProperties
 * @covers SMWSpecialUnusedProperties
 * @covers SMWSpecialProperties
 * @covers SMW\SpecialConcepts
 *
 * @note Test base was borrowed from the EducationProgram extension
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SpecialsTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * @dataProvider getSpecialPageProvider
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

		$this->assertTrue( true, 'SpecialPage test did run without errors' );
	}

	/**
	 * @test SpecialPageFactory::getLocalNameFor
	 * @dataProvider getSpecialPageProvider
	 *
	 * Test created in response to bug 44191
	 *
	 * @param $specialPage
	 */
	public function testSpecialAliasesContLang( SpecialPage $specialPage ) {

		// Test for languages
		$langCodes = array( 'en', 'fr', 'de', 'es', 'zh', 'ja' );

		// Test aliases for a specific language
		foreach ( $langCodes as $langCode ) {
			$langObj = Language::factory( $langCode );
			$aliases = $langObj->getSpecialPageAliases();
			$found = false;
			$name = $specialPage->getName();

			// Check against available aliases
			foreach ( $aliases as $n => $values ) {
				foreach ( $values as $value ) {
					if( $name === $value ) {
						$found = true;
						break;
					}
				}
			}

			$this->assertTrue( $found, "{$name} alias not found in language {$langCode}" );
		}
	}

	/**
	 * Provides special pages
	 *
	 * @return array
	 */
	public function getSpecialPageProvider() {
		$request = new FauxRequest( array(), true );
		$argLists = array();

		$specialPages = array(
			'Ask',
			'Browse',
			'PageProperty',
			'SearchByProperty',
			'SMWAdmin',
			'SemanticStatistics',
			'ExportRDF',
			'Types',
			'Properties',
			'UnusedProperties',
			'WantedProperties',
			'Concepts'

			// Can't be tested because of

			// FIXME Test fails with Undefined index: HTTP_ACCEPT
			// 'URIResolver'

		);

		foreach ( $specialPages as $special ) {

			if ( array_key_exists( $special, $GLOBALS['wgSpecialPages'] ) ) {

				$specialPage = SpecialPageFactory::getPage( $special );
				$context = RequestContext::newExtraneousContext( $specialPage->getTitle() );
				$context->setRequest( $request );

				$specialPage->setContext( clone $context );
				$argLists[] = array( clone $specialPage );

				$context->setUser( $this->getUser() );
				$specialPage->setContext( $context );
				$argLists[] = array( $specialPage );
			}
		}

		return $argLists;
	}
}
