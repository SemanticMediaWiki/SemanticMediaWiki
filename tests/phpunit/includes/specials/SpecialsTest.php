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
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */

/**
 * @covers \SMW\SpecialSemanticStatistics
 * @covers \SMW\SpecialWantedProperties
 * @covers \SMW\SpecialUnusedProperties
 * @covers \SMW\SpecialProperties
 * @covers \SMW\SpecialConcepts
 * @covers \SMW\SpecialPage
 * @covers SMWAskPage
 * @covers SMWSpecialBrowse
 * @covers SMWAdmin
 * @covers \SMW\MediaWiki\Specials\SpecialSearchByProperty
 *
 * @note Test base was borrowed from the EducationProgram extension
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
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
	 * @dataProvider specialPageProvider
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
	 * @dataProvider specialPageProvider
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
	public function specialPageProvider() {
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

				// Deprecated: Use of SpecialPage::getTitle was deprecated in MediaWiki 1.23
				$title = method_exists( $specialPage, 'getPageTitle') ? $specialPage->getPageTitle() : $specialPage->getTitle();

				$context = RequestContext::newExtraneousContext( $title );
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
